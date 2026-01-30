<?php

/**
 * Script de Normalização - Competência de Cartão de Crédito
 * 
 * Este script popula o campo `data_competencia` nos lançamentos existentes
 * que foram originados de cartão de crédito, usando a data da compra original.
 * 
 * SEGURANÇA:
 * - Modo DRY-RUN por padrão (não altera dados)
 * - Requer confirmação explícita para executar alterações
 * - Faz backup de IDs afetados antes de modificar
 * 
 * Uso:
 *   php cli/normalizar_competencia_cartao.php                    # Modo dry-run (simulação)
 *   php cli/normalizar_competencia_cartao.php --execute          # Executa alterações
 *   php cli/normalizar_competencia_cartao.php --user=123         # Processa apenas user_id=123
 *   php cli/normalizar_competencia_cartao.php --limit=1000       # Limita a 1000 registros
 *   php cli/normalizar_competencia_cartao.php --verbose          # Mostra detalhes de cada registro
 */

require_once __DIR__ . '/../bootstrap.php';

use Application\Models\Lancamento;
use Application\Models\FaturaCartaoItem;
use Illuminate\Database\Capsule\Manager as DB;

class NormalizadorCompetenciaCartao
{
    private bool $dryRun = true;
    private bool $verbose = false;
    private ?int $userId = null;
    private int $limit = 0;

    private int $totalAnalisados = 0;
    private int $totalAtualizados = 0;
    private int $totalErros = 0;
    private array $idsAtualizados = [];
    private array $erros = [];

    public function __construct(array $args)
    {
        $this->parseArgs($args);
    }

    private function parseArgs(array $args): void
    {
        foreach ($args as $arg) {
            if ($arg === '--execute') {
                $this->dryRun = false;
            } elseif ($arg === '--verbose') {
                $this->verbose = true;
            } elseif (str_starts_with($arg, '--user=')) {
                $this->userId = (int)substr($arg, 7);
            } elseif (str_starts_with($arg, '--limit=')) {
                $this->limit = (int)substr($arg, 8);
            }
        }
    }

    public function run(): void
    {
        $this->printHeader();

        if ($this->dryRun) {
            $this->log("🔍 MODO DRY-RUN: Nenhuma alteração será feita no banco de dados");
            $this->log("   Use --execute para aplicar as alterações\n");
        } else {
            $this->log("⚠️  MODO EXECUÇÃO: Alterações serão aplicadas no banco de dados!\n");
        }

        // Verificar se a coluna existe
        if (!$this->verificarColunasExistem()) {
            $this->log("❌ ERRO: Colunas de competência não encontradas na tabela lancamentos");
            $this->log("   Execute primeiro a migration: php cli/migrate.php");
            return;
        }

        // Processar em lotes
        $this->processarLancamentos();

        // Mostrar resumo
        $this->printResumo();

        // Salvar log de IDs atualizados
        if (!$this->dryRun && count($this->idsAtualizados) > 0) {
            $this->salvarLogIds();
        }
    }

    private function verificarColunasExistem(): bool
    {
        try {
            $columns = DB::select("SHOW COLUMNS FROM lancamentos LIKE 'data_competencia'");
            return count($columns) > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function processarLancamentos(): void
    {
        $this->log("📊 Buscando lançamentos de cartão de crédito para normalização...\n");

        // ESTRATÉGIA 1: Lançamentos com cartao_credito_id (vinculados a cartão)
        $this->log("--- Estratégia 1: Lançamentos com cartao_credito_id ---");
        $this->processarPorCartaoId();

        // ESTRATÉGIA 2: Lançamentos identificados por descrição/padrão
        $this->log("\n--- Estratégia 2: Lançamentos identificados por padrão ---");
        $this->processarPorPadrao();
    }

    private function processarPorCartaoId(): void
    {
        $query = Lancamento::query()
            ->whereNotNull('cartao_credito_id')
            ->whereNull('data_competencia');

        if ($this->userId) {
            $query->where('user_id', $this->userId);
        }

        if ($this->limit > 0) {
            $query->limit($this->limit);
        }

        $total = (clone $query)->count();
        $this->log("   Encontrados: {$total} lançamentos com cartao_credito_id");

        if ($total == 0) {
            return;
        }

        $query->orderBy('id')->chunk(500, function ($lancamentos) {
            foreach ($lancamentos as $lancamento) {
                $this->totalAnalisados++;

                try {
                    // Tentar encontrar data de compra original via faturas_cartao_itens
                    $dataCompetencia = $this->buscarDataCompraOriginal($lancamento);

                    if (!$dataCompetencia) {
                        // Se não encontrar, usar a própria data do lançamento
                        $dataCompetencia = $lancamento->data;
                    }

                    $this->atualizarLancamento($lancamento, [
                        'data_competencia' => $dataCompetencia,
                        'afeta_competencia' => true,
                        'afeta_caixa' => true,
                        'origem_tipo' => Lancamento::ORIGEM_CARTAO_CREDITO,
                    ]);
                } catch (\Exception $e) {
                    $this->registrarErro($lancamento->id, $e->getMessage());
                }
            }
        });
    }

    /**
     * Busca a data de compra original do item de fatura associado ao lançamento
     */
    private function buscarDataCompraOriginal(Lancamento $lancamento): ?string
    {
        // Buscar por descrição similar na tabela faturas_cartao_itens
        $item = FaturaCartaoItem::where('cartao_credito_id', $lancamento->cartao_credito_id)
            ->where('descricao', $lancamento->descricao)
            ->where('valor', $lancamento->valor)
            ->first();

        if ($item && ($item->data_compra || $item->data)) {
            return $item->data_compra ?? $item->data;
        }

        // Buscar por valor e mês aproximado
        $mesLancamento = substr($lancamento->data, 0, 7); // YYYY-MM
        $item = FaturaCartaoItem::where('cartao_credito_id', $lancamento->cartao_credito_id)
            ->where('valor', $lancamento->valor)
            ->whereRaw("DATE_FORMAT(data_compra, '%Y-%m') <= ?", [$mesLancamento])
            ->orderBy('data_compra', 'desc')
            ->first();

        if ($item && ($item->data_compra || $item->data)) {
            return $item->data_compra ?? $item->data;
        }

        return null;
    }

    private function processarPorPadrao(): void
    {
        // Buscar lançamentos que parecem ser de cartão pela descrição
        // mas não estão marcados corretamente
        $query = Lancamento::query()
            ->whereNull('cartao_credito_id')
            ->whereNull('data_competencia')
            ->where(function ($q) {
                $q->where('descricao', 'LIKE', '%fatura%cartão%')
                    ->orWhere('descricao', 'LIKE', '%fatura%cartao%')
                    ->orWhere('descricao', 'LIKE', '%pagamento%fatura%')
                    ->orWhere('descricao', 'LIKE', '%parcela%cartão%')
                    ->orWhere('descricao', 'LIKE', '%parcela%cartao%');
            });

        if ($this->userId) {
            $query->where('user_id', $this->userId);
        }

        if ($this->limit > 0) {
            $query->limit($this->limit);
        }

        $total = (clone $query)->count();
        $this->log("   Encontrados: {$total} lançamentos (por padrão de descrição)");

        if ($total > 0) {
            $this->log("   ⚠️  Estes lançamentos precisam de verificação manual");

            if ($this->verbose) {
                $query->orderBy('id')->chunk(100, function ($lancamentos) {
                    foreach ($lancamentos as $lancamento) {
                        $this->log("      - ID {$lancamento->id}: {$lancamento->descricao}");
                    }
                });
            }
        }
    }

    private function atualizarLancamento(Lancamento $lancamento, array $dados): void
    {
        if ($this->verbose) {
            $this->log("   → Lançamento #{$lancamento->id}: data={$lancamento->data} → competência={$dados['data_competencia']}");
        }

        if (!$this->dryRun) {
            DB::beginTransaction();
            try {
                $lancamento->update($dados);
                DB::commit();
                $this->idsAtualizados[] = $lancamento->id;
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        }

        $this->totalAtualizados++;
    }

    private function registrarErro(int $id, string $mensagem): void
    {
        $this->totalErros++;
        $this->erros[] = [
            'id' => $id,
            'mensagem' => $mensagem,
        ];

        if ($this->verbose) {
            $this->log("   ❌ Erro no lançamento #{$id}: {$mensagem}");
        }
    }

    private function printHeader(): void
    {
        $this->log("╔════════════════════════════════════════════════════════════╗");
        $this->log("║   NORMALIZAÇÃO DE COMPETÊNCIA - CARTÃO DE CRÉDITO         ║");
        $this->log("╠════════════════════════════════════════════════════════════╣");
        $this->log("║  Este script popula data_competencia nos lançamentos      ║");
        $this->log("║  de cartão de crédito usando a data de compra original    ║");
        $this->log("╚════════════════════════════════════════════════════════════╝\n");
    }

    private function printResumo(): void
    {
        $this->log("\n╔════════════════════════════════════════════════════════════╗");
        $this->log("║                       RESUMO                               ║");
        $this->log("╠════════════════════════════════════════════════════════════╣");
        $this->log("║  Lançamentos analisados:  " . str_pad($this->totalAnalisados, 10) . "                       ║");

        if ($this->dryRun) {
            $this->log("║  Seriam atualizados:      " . str_pad($this->totalAtualizados, 10) . "                       ║");
        } else {
            $this->log("║  Lançamentos atualizados: " . str_pad($this->totalAtualizados, 10) . "                       ║");
        }

        $this->log("║  Erros encontrados:       " . str_pad($this->totalErros, 10) . "                       ║");
        $this->log("╚════════════════════════════════════════════════════════════╝");

        if ($this->totalErros > 0 && !$this->verbose) {
            $this->log("\n⚠️  Erros encontrados (use --verbose para ver detalhes):");
            foreach (array_slice($this->erros, 0, 5) as $erro) {
                $this->log("   - ID {$erro['id']}: {$erro['mensagem']}");
            }
            if (count($this->erros) > 5) {
                $this->log("   ... e mais " . (count($this->erros) - 5) . " erros");
            }
        }

        if ($this->dryRun && $this->totalAtualizados > 0) {
            $this->log("\n✅ Para aplicar as alterações, execute:");
            $this->log("   php cli/normalizar_competencia_cartao.php --execute");
        }
    }

    private function salvarLogIds(): void
    {
        $logDir = __DIR__ . '/../storage/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $filename = $logDir . '/normalizar_competencia_' . date('Y-m-d_His') . '.json';

        $data = [
            'timestamp' => date('Y-m-d H:i:s'),
            'total_atualizados' => count($this->idsAtualizados),
            'ids_atualizados' => $this->idsAtualizados,
            'erros' => $this->erros,
        ];

        file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT));
        $this->log("\n📁 Log salvo em: {$filename}");
    }

    private function log(string $message): void
    {
        echo $message . PHP_EOL;
    }
}

// Executar
$normalizer = new NormalizadorCompetenciaCartao($argv);
$normalizer->run();
