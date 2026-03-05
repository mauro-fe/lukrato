<?php

namespace Application\Services\Cartao;

use Application\Models\FaturaCartaoItem;
use Application\Models\CartaoCredito;
use Application\Models\Fatura;
use Application\Enums\Recorrencia;
use Illuminate\Database\Capsule\Manager as DB;
use Application\Services\Infrastructure\LogService;

/**
 * Serviço para gerar automaticamente itens recorrentes de cartão de crédito.
 * 
 * Executado pelo cron (SchedulerController::runAll).
 * 
 * Lógica:
 * 1. Busca todos os itens "pai" de recorrência ativa (recorrente=true, recorrencia_pai_id IS NULL, cancelado_em IS NULL)
 * 2. Para cada um, calcula o próximo mês/ano com base na frequência
 * 3. Verifica se já existe um item filho para esse mês/ano
 * 4. Se não existir, cria o novo FaturaCartaoItem vinculado à fatura do mês correspondente
 * 5. Idempotente: pode rodar várias vezes sem duplicar
 */
class RecorrenciaCartaoService
{
    /**
     * Processar todas as recorrências ativas e gerar itens para o mês vigente/próximo
     * 
     * @return array Estatísticas do processamento
     */
    public function processRecurringCardItems(): array
    {
        $stats = [
            'processados' => 0,
            'criados' => 0,
            'ignorados' => 0,
            'expirados' => 0,
            'erros' => [],
        ];

        // Buscar todos os itens pai de recorrência ativa
        $itensPai = FaturaCartaoItem::where('recorrente', true)
            ->whereNull('recorrencia_pai_id')  // É o item original (pai)
            ->whereNull('cancelado_em')         // Não foi cancelado
            ->get();

        LogService::info("[RECORRENCIA_CARTAO] Iniciando processamento", [
            'total_recorrencias_ativas' => $itensPai->count(),
        ]);

        foreach ($itensPai as $itemPai) {
            $stats['processados']++;

            try {
                $resultado = $this->processarRecorrencia($itemPai);

                if ($resultado === 'criado') {
                    $stats['criados']++;
                } elseif ($resultado === 'expirado') {
                    $stats['expirados']++;
                } else {
                    $stats['ignorados']++;
                }
            } catch (\Throwable $e) {
                $stats['erros'][] = [
                    'item_pai_id' => $itemPai->id,
                    'descricao' => $itemPai->descricao,
                    'erro' => $e->getMessage(),
                ];
                LogService::error("[RECORRENCIA_CARTAO] Erro ao processar item {$itemPai->id}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        LogService::info("[RECORRENCIA_CARTAO] Processamento concluído", $stats);

        return $stats;
    }

    /**
     * Processar uma recorrência individual.
     *
     * Gera no máximo 1 item por execução, apenas quando a data do próximo ciclo
     * já chegou (mesma cadência escolhida na assinatura).
     *
     * @return string 'criado' | 'ignorado' | 'expirado'
     */
    private function processarRecorrencia(FaturaCartaoItem $itemPai): string
    {
        // Verificar se a recorrência expirou
        if ($itemPai->recorrencia_fim !== null && $itemPai->recorrencia_fim->lt(now()->startOfDay())) {
            LogService::info("[RECORRENCIA_CARTAO] Recorrência expirada", [
                'item_id' => $itemPai->id,
                'fim' => $itemPai->recorrencia_fim->format('Y-m-d'),
            ]);
            return 'expirado';
        }

        // Buscar cartão
        $cartao = CartaoCredito::forUser($itemPai->user_id)->find($itemPai->cartao_credito_id);
        if (!$cartao || $cartao->arquivado) {
            return 'ignorado';
        }

        $agora = now()->startOfDay();
        $freq = Recorrencia::tryFromString($itemPai->recorrencia_freq) ?? Recorrencia::MENSAL;

        // Encontrar o último item gerado (filho ou pai)
        $ultimoItem = FaturaCartaoItem::where(function ($q) use ($itemPai) {
            $q->where('recorrencia_pai_id', $itemPai->id)
                ->orWhere('id', $itemPai->id);
        })
            ->whereNull('cancelado_em')
            ->orderByRaw('ano_referencia DESC, mes_referencia DESC, id DESC')
            ->first();

        if (!$ultimoItem) {
            return 'ignorado';
        }

        $baseDataCompra = \Carbon\Carbon::parse($ultimoItem->data_compra ?? $itemPai->data_compra)->startOfDay();
        $proximaDataCompra = $baseDataCompra->copy();
        $this->avancarPorFrequencia($proximaDataCompra, $freq);

        // Ainda não chegou o ciclo da próxima recorrência.
        if ($agora->lt($proximaDataCompra)) {
            return 'ignorado';
        }

        // Verificar se a recorrência já terminou.
        if ($itemPai->recorrencia_fim !== null && $proximaDataCompra->gt($itemPai->recorrencia_fim->copy()->startOfDay())) {
            return 'expirado';
        }

        $mes = (int)$proximaDataCompra->format('n');
        $ano = (int)$proximaDataCompra->format('Y');

        // Verificar se já existe item filho (ou o próprio pai) para este mês/ano
        $jaExiste = FaturaCartaoItem::where('cartao_credito_id', $itemPai->cartao_credito_id)
            ->where('user_id', $itemPai->user_id)
            ->where(function ($q) use ($itemPai) {
                $q->where('recorrencia_pai_id', $itemPai->id)
                    ->orWhere('id', $itemPai->id);
            })
            ->where('mes_referencia', $mes)
            ->where('ano_referencia', $ano)
            ->whereNull('cancelado_em')
            ->exists();

        if ($jaExiste) {
            return 'ignorado';
        }

        // Criar apenas o próximo item devido.
        try {
            $this->criarItemRecorrente($itemPai, $cartao, $mes, $ano, $proximaDataCompra);
            return 'criado';
        } catch (\Throwable $e) {
            if (str_contains(strtolower($e->getMessage()), 'duplicate')) {
                LogService::info('[RECORRENCIA_CARTAO] Duplicata evitada por unique key', [
                    'item_pai_id' => $itemPai->id,
                    'mes' => $mes,
                    'ano' => $ano,
                ]);
                return 'ignorado';
            }

            throw $e;
        }
    }

    /**
     * Calcular os meses que precisam ter itens gerados
     * 
     * Gera para o mês atual e o próximo (se a freq for mensal).
     * Para frequências maiores, calcula baseado na última geração.
     */
    private function calcularMesesAlvo(FaturaCartaoItem $itemPai, \Carbon\Carbon $agora): array
    {
        $freq = Recorrencia::tryFromString($itemPai->recorrencia_freq);
        if (!$freq) {
            $freq = Recorrencia::MENSAL;
        }

        // Encontrar o último item gerado (filho ou pai)
        $ultimoItem = FaturaCartaoItem::where(function ($q) use ($itemPai) {
            $q->where('recorrencia_pai_id', $itemPai->id)
                ->orWhere('id', $itemPai->id);
        })
            ->whereNull('cancelado_em')
            ->orderByRaw('ano_referencia DESC, mes_referencia DESC')
            ->first();

        $ultimoMes = $ultimoItem->mes_referencia ?? $itemPai->mes_referencia;
        $ultimoAno = $ultimoItem->ano_referencia ?? $itemPai->ano_referencia;

        $meses = [];
        $dataIteracao = \Carbon\Carbon::createFromDate($ultimoAno, $ultimoMes, 1);

        // Avançar pela frequência e gerar até 2 meses à frente do mês atual
        $limite = $agora->copy()->addMonth()->endOfMonth();

        // Avançar uma vez para o próximo período (o último já existe)
        $this->avancarPorFrequencia($dataIteracao, $freq);

        while ($dataIteracao->lte($limite)) {
            $meses[] = [
                'mes' => (int)$dataIteracao->format('n'),
                'ano' => (int)$dataIteracao->format('Y'),
            ];
            $this->avancarPorFrequencia($dataIteracao, $freq);
        }

        return $meses;
    }

    /**
     * Avançar data pela frequência de recorrência
     */
    private function avancarPorFrequencia(\Carbon\Carbon $data, Recorrencia $freq): void
    {
        match ($freq) {
            Recorrencia::SEMANAL => $data->addWeek(),
            Recorrencia::QUINZENAL => $data->addWeeks(2),
            Recorrencia::MENSAL => $data->addMonth(),
            Recorrencia::BIMESTRAL => $data->addMonths(2),
            Recorrencia::TRIMESTRAL => $data->addMonths(3),
            Recorrencia::SEMESTRAL => $data->addMonths(6),
            Recorrencia::ANUAL => $data->addYear(),
        };
    }

    /**
     * Criar item de fatura recorrente filho
     */
    private function criarItemRecorrente(
        FaturaCartaoItem $itemPai,
        CartaoCredito $cartao,
        int $mes,
        int $ano,
        ?\Carbon\Carbon $dataCompra = null
    ): FaturaCartaoItem {
        // Calcular data de vencimento para este mês
        $ultimoDiaMes = (int)date('t', mktime(0, 0, 0, $mes, 1, $ano));
        $diaVencimento = min($cartao->dia_vencimento, $ultimoDiaMes);
        $dataVencimento = sprintf('%04d-%02d-%02d', $ano, $mes, $diaVencimento);

        // Buscar ou criar fatura do mês
        $fatura = $this->buscarOuCriarFatura($itemPai->user_id, $cartao->id, $mes, $ano);

        $item = FaturaCartaoItem::create([
            'user_id' => $itemPai->user_id,
            'cartao_credito_id' => $cartao->id,
            'fatura_id' => $fatura->id,
            'lancamento_id' => null,
            'descricao' => $itemPai->descricao,
            'valor' => $itemPai->valor,
            'data_compra' => ($dataCompra ?? now())->format('Y-m-d'),
            'data_vencimento' => $dataVencimento,
            'categoria_id' => $itemPai->categoria_id,
            'parcela_atual' => 1,
            'total_parcelas' => 1,
            'mes_referencia' => $mes,
            'ano_referencia' => $ano,
            'pago' => false,
            // Campos de recorrência
            'recorrente' => true,
            'recorrencia_freq' => $itemPai->recorrencia_freq,
            'recorrencia_fim' => $itemPai->recorrencia_fim,
            'recorrencia_pai_id' => $itemPai->id,
        ]);

        // Atualizar valor total da fatura
        $fatura->valor_total += $itemPai->valor;
        $fatura->save();

        // Atualizar limite disponível do cartão
        $this->atualizarLimiteCartao($cartao->id, $itemPai->valor, $itemPai->user_id);

        LogService::info("[RECORRENCIA_CARTAO] Item recorrente criado", [
            'item_id' => $item->id,
            'item_pai_id' => $itemPai->id,
            'fatura_id' => $fatura->id,
            'descricao' => $itemPai->descricao,
            'valor' => $itemPai->valor,
            'mes_ano' => "{$mes}/{$ano}",
            'data_compra' => ($dataCompra ?? now())->format('Y-m-d'),
        ]);

        return $item;
    }

    /**
     * Cancelar uma recorrência ativa
     * 
     * Marca o item pai e todos os filhos futuros (não pagos) como cancelados.
     * Itens já pagos permanecem intactos.
     * 
     * @return array Resultado do cancelamento
     */
    public function cancelarRecorrencia(int $itemPaiId, int $userId): array
    {
        $itemPai = FaturaCartaoItem::where('id', $itemPaiId)
            ->where('user_id', $userId)
            ->where('recorrente', true)
            ->whereNull('recorrencia_pai_id')
            ->first();

        if (!$itemPai) {
            return ['success' => false, 'message' => 'Recorrência não encontrada.'];
        }

        if ($itemPai->cancelado_em !== null) {
            return ['success' => false, 'message' => 'Recorrência já estava cancelada.'];
        }

        DB::beginTransaction();
        try {
            $agora = now();

            // Cancelar item pai
            $itemPai->cancelado_em = $agora;
            $itemPai->save();

            // Cancelar filhos futuros (não pagos)
            $filhosCancelados = FaturaCartaoItem::where('recorrencia_pai_id', $itemPai->id)
                ->where('pago', false)
                ->whereNull('cancelado_em')
                ->get();

            foreach ($filhosCancelados as $filho) {
                $filho->cancelado_em = $agora;
                $filho->save();

                // Devolver valor à fatura
                $fatura = Fatura::forUser($userId)->find($filho->fatura_id);
                if ($fatura) {
                    $fatura->valor_total -= $filho->valor;
                    $fatura->save();
                }

                // Devolver limite ao cartão
                $this->atualizarLimiteCartao($filho->cartao_credito_id, $filho->valor, $userId, 'credito');
            }

            DB::commit();

            LogService::info("[RECORRENCIA_CARTAO] Recorrência cancelada", [
                'item_pai_id' => $itemPai->id,
                'descricao' => $itemPai->descricao,
                'filhos_cancelados' => $filhosCancelados->count(),
            ]);

            return [
                'success' => true,
                'message' => 'Assinatura cancelada com sucesso.',
                'filhos_cancelados' => $filhosCancelados->count(),
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            LogService::error("[RECORRENCIA_CARTAO] Erro ao cancelar recorrência", [
                'item_pai_id' => $itemPaiId,
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'message' => 'Erro ao cancelar assinatura: ' . $e->getMessage()];
        }
    }

    /**
     * Listar recorrências ativas de um usuário (opcionalmente filtrar por cartão)
     */
    public function listarRecorrenciasAtivas(int $userId, ?int $cartaoId = null): array
    {
        $query = FaturaCartaoItem::where('user_id', $userId)
            ->where('recorrente', true)
            ->whereNull('recorrencia_pai_id')
            ->whereNull('cancelado_em');

        if ($cartaoId) {
            $query->where('cartao_credito_id', $cartaoId);
        }

        $itens = $query->orderBy('descricao')->get();

        return $itens->map(function ($item) {
            $totalFilhos = FaturaCartaoItem::where('recorrencia_pai_id', $item->id)
                ->whereNull('cancelado_em')
                ->count();

            return [
                'id' => $item->id,
                'descricao' => $item->descricao,
                'valor' => $item->valor,
                'recorrencia_freq' => $item->recorrencia_freq,
                'recorrencia_fim' => $item->recorrencia_fim?->format('Y-m-d'),
                'cartao_credito_id' => $item->cartao_credito_id,
                'categoria_id' => $item->categoria_id,
                'data_compra' => $item->data_compra?->format('Y-m-d'),
                'total_meses_cobrado' => $totalFilhos + 1, // +1 conta o próprio pai
            ];
        })->toArray();
    }

    // ─── Métodos auxiliares ───────────────────────────────────

    /**
     * Buscar ou criar fatura mensal (mesma lógica de CartaoCreditoLancamentoService)
     */
    private function buscarOuCriarFatura(int $userId, int $cartaoId, int $mes, int $ano): Fatura
    {
        $descricao = "Fatura {$mes}/{$ano}";

        $fatura = Fatura::where('user_id', $userId)
            ->where('cartao_credito_id', $cartaoId)
            ->where('descricao', $descricao)
            ->first();

        if (!$fatura) {
            $fatura = Fatura::create([
                'user_id' => $userId,
                'cartao_credito_id' => $cartaoId,
                'descricao' => $descricao,
                'valor_total' => 0,
                'numero_parcelas' => 0,
                'data_compra' => date('Y-m-d'),
            ]);

            LogService::info('[RECORRENCIA_CARTAO] Nova fatura mensal criada', [
                'fatura_id' => $fatura->id,
                'cartao_id' => $cartaoId,
                'mes' => $mes,
                'ano' => $ano,
            ]);
        }

        return $fatura;
    }

    /**
     * Atualizar limite do cartão
     * Recalcula do zero a partir dos itens não pagos para evitar drift
     */
    private function atualizarLimiteCartao(int $cartaoId, float $valor, int $userId, string $tipo = 'debito'): void
    {
        $cartao = CartaoCredito::forUser($userId)->find($cartaoId);
        if (!$cartao) {
            return;
        }

        $cartao->atualizarLimiteDisponivel();
    }
}
