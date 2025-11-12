<?php

namespace Application\Services;

use Application\Models\Investimento;
use Application\Models\TransacaoInvestimento;
use Application\Models\Provento;
use Application\Models\CategoriaInvestimento;
use Application\Core\Exceptions\ValidationException;
use Application\Services\LogService;
use GUMP;
use Throwable;


enum TransacaoTipo: string
{
    case COMPRA = 'compra';
    case VENDA = 'venda';
    public static function listValues(): string
    {
        return implode(';', array_column(self::cases(), 'value'));
    }
}

enum ProventoTipo: string
{
    case DIVIDENDO = 'dividendo';
    case JCP = 'jcp';
    case RENDIMENTO = 'rendimento';
    public static function listValues(): string
    {
        return implode(';', array_column(self::cases(), 'value'));
    }
}


class InvestimentoService
{
    // --- Métodos Auxiliares ---

    /** Normaliza números com vírgula para ponto. */
    private function normalizeNumerics(array &$data, array $keys): void
    {
        foreach ($keys as $k) {
            if (isset($data[$k]) && $data[$k] !== '') {
                $data[$k] = str_replace(',', '.', (string)$data[$k]);
            }
        }
    }

    /** * Busca um investimento ou falha (404).
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    private function findOrFail(int $id, int $userId): Investimento
    {
        /** @var Investimento|null $inv */
        $inv = Investimento::where('user_id', $userId)->find($id);
        if (!$inv) {
            // Lança uma exceção que o Controller (API) pode pegar e transformar em 404
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException('Investimento não encontrado');
        }
        return $inv;
    }
    
    // --- Lógica de Negócio (Cálculos) ---

    /** Calcula as métricas de rentabilidade de um investimento. */
    public function calcularMetricas(Investimento $i): array
    {
        $investido = (float)$i->quantidade * (float)$i->preco_medio;
        $atual     = (float)$i->quantidade * (float)($i->preco_atual ?? $i->preco_medio ?? 0);
        $lucro     = $atual - $investido;
        $rentab    = $investido > 0 ? ($lucro / $investido) * 100 : 0.0;

        return [
            'valor_investido' => round($investido, 2),
            'valor_atual'     => round($atual, 2),
            'lucro'           => round($lucro, 2),
            'rentabilidade'   => round($rentab, 2),
        ];
    }

    /** Retorna estatísticas agregadas (total, lucro, etc). */
    public function getStats(int $uid): array
    {
        try {
            $itens = Investimento::where('user_id', $uid)->get();
            $totInvestido = 0.0;
            $totAtual = 0.0;

            foreach ($itens as $i) {
                $metrics = $this->calcularMetricas($i);
                $totInvestido += $metrics['valor_investido'];
                $totAtual += $metrics['valor_atual'];
            }

            $lucro = $totAtual - $totInvestido;
            $rent = $totInvestido > 0 ? round(($lucro / $totInvestido) * 100, 2) : 0.0;

            return [
                'total_investido'  => round($totInvestido, 2),
                'valor_atual'      => round($totAtual, 2),
                'lucro'            => round($lucro, 2),
                'rentabilidade'    => $rent,
                'quantidade_itens' => (int)$itens->count(),
            ];
        } catch (Throwable $e) {
            LogService::error('Falha ao calcular Stats de Investimentos', [
                'user_id' => $uid,
                'exception' => $e->getMessage()
            ]);
            throw $e; // Relança para o controller
        }
    }
    
    // --- Métodos de Acesso a Dados (CRUD) ---

    /** Lista todos os investimentos do usuário com filtros. */
    public function getInvestimentos(int $uid, array $filters): array
    {
        try {
            $q           = trim((string)($filters['q'] ?? ''));
            $categoriaId = (int)($filters['categoria_id'] ?? 0);
            $contaId     = (int)($filters['conta_id'] ?? 0);
            $ticker      = trim((string)($filters['ticker'] ?? ''));
            $order       = $filters['order'] ?? 'nome';
            $dir         = $filters['dir'] ?? 'asc';

            $query = Investimento::with('categoria')->where('user_id', $uid);

            if ($q !== '') {
                $query->where(fn($w) => $w->where('nome', 'like', "%{$q}%")->orWhere('ticker', 'like', "%{$q}%"));
            }
            if ($categoriaId > 0) $query->where('categoria_id', $categoriaId);
            if ($contaId > 0)     $query->where('conta_id', $contaId);
            if ($ticker !== '')   $query->where('ticker', $ticker);

            return $query->orderBy($order, $dir)->get()->map(function (Investimento $i) {
                $metrics  = $this->calcularMetricas($i);
                $category = $i->categoria;
                $categoryName = $category?->nome ?? 'Sem categoria';
                $categoryColor = $category?->cor ?? '#475569';
                $currentPrice = $i->preco_atual !== null ? (float)$i->preco_atual : null;

                return array_merge([
                    'id'              => (int)$i->id,
                    'categoria_id'    => (int)$i->categoria_id,
                    'conta_id'        => $i->conta_id ? (int)$i->conta_id : null,
                    'categoria_nome'  => $categoryName,
                    'category_name'   => $categoryName,
                    'cor'             => $categoryColor,
                    'color'           => $categoryColor,
                    'nome'            => (string)$i->nome,
                    'name'            => (string)$i->nome,
                    'ticker'          => $i->ticker,
                    'quantidade'      => (float)$i->quantidade,
                    'quantity'        => (float)$i->quantidade,
                    'preco_medio'     => (float)$i->preco_medio,
                    'avg_price'       => (float)$i->preco_medio,
                    'preco_atual'     => $currentPrice,
                    'current_price'   => $currentPrice,
                    'atualizado_em'   => (string)$i->atualizado_em,
                ], $metrics);
            })->all();
        } catch (Throwable $e) {
            LogService::error('Falha ao listar investimentos no Service', [
                'user_id' => $uid,
                'exception' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /** Busca um único investimento. */
    public function getInvestimentoById(int $id, int $uid): array
    {
        try {
            $i = $this->findOrFail($id, $uid); // Pode lançar ModelNotFoundException
            $metrics = $this->calcularMetricas($i);

            return array_merge([
                'id'              => (int)$i->id,
                'categoria_id'    => (int)$i->categoria_id,
                'conta_id'        => $i->conta_id ? (int)$i->conta_id : null,
                'nome'            => (string)$i->nome,
                'ticker'          => $i->ticker,
                'quantidade'      => (float)$i->quantidade,
                'preco_medio'     => (float)$i->preco_medio,
                'preco_atual'     => $i->preco_atual !== null ? (float)$i->preco_atual : null,
                'observacoes'     => $i->observacoes,
                'atualizado_em'   => (string)$i->atualizado_em,
            ], $metrics);
        } catch (Throwable $e) {
            // Não logamos ModelNotFound, pois é um 404 esperado
            if (!($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException)) {
                LogService::error('Falha ao buscar investimento por ID', [
                    'user_id' => $uid,
                    'investimento_id' => $id,
                    'exception' => $e->getMessage()
                ]);
            }
            throw $e;
        }
    }

    /** Cria um novo investimento. */
    public function criarInvestimento(int $userId, array $data): Investimento
    {
        try {
            $gump = new GUMP();
            $this->normalizeNumerics($data, ['quantidade', 'preco_medio', 'preco_atual']);

            $gump->validation_rules([
                'categoria_id' => 'required|integer|min_numeric,1',
                'conta_id'     => 'integer',
                'nome'         => 'required|min_len,2|max_len,200',
                'ticker'       => 'max_len,20',
                'quantidade'   => 'required|numeric|min_numeric,0.0001',
                'preco_medio'  => 'required|numeric|min_numeric,0',
                'preco_atual'  => 'numeric',
                'data_compra'  => 'date',
            ]);
            $gump->filter_rules([
                'nome'        => 'trim|sanitize_string',
                'ticker'      => 'trim|sanitize_string|upper',
                'observacoes' => 'trim',
            ]);

            $validData = $gump->run($data);
            if ($validData === false) {
                // Lança exceção de validação (esperada, não loga como erro)
                throw new ValidationException($gump->get_errors_array(), 'Falha na validação', 422);
            }

            $inv = new Investimento();
            $inv->user_id      = $userId;
            $inv->categoria_id = (int)$validData['categoria_id'];
            $inv->conta_id     = !empty($validData['conta_id']) ? (int)$validData['conta_id'] : null;
            $inv->nome         = (string)$validData['nome'];
            $inv->ticker       = $validData['ticker'] ?: null;
            $inv->quantidade   = (float)$validData['quantidade'];
            $inv->preco_medio  = (float)$validData['preco_medio'];
            $inv->preco_atual  = ($validData['preco_atual'] !== '' && $validData['preco_atual'] !== null) ? (float)$validData['preco_atual'] : null;
            $inv->data_compra  = !empty($validData['data_compra']) ? $validData['data_compra'] : null;
            $inv->observacoes  = $validData['observacoes'] ?: null;
            $inv->save();

            // ✅ Log de Sucesso
            LogService::info('Investimento criado', [
                'user_id' => $userId,
                'investimento_id' => $inv->id,
                'nome' => $inv->nome
            ]);

            return $inv;
        } catch (ValidationException $e) {
            throw $e; // Relança para o controller (422)
        } catch (Throwable $e) {
            // 🛑 Log de Erro Inesperado
            LogService::error('Falha ao criar investimento no Service', [
                'user_id' => $userId,
                'data_keys' => implode(',', array_keys($data)), // Evita logar dados sensíveis, se houver
                'exception' => $e->getMessage()
            ]);
            throw $e; // Relança para o controller (500)
        }
    }

    /** Atualiza um investimento. */
    public function atualizarInvestimento(int $id, int $uid, array $payload): Investimento
    {
        try {
            $inv = $this->findOrFail($id, $uid); // Pode lançar ModelNotFound

            $gump = new GUMP();
            $this->normalizeNumerics($payload, ['quantidade', 'preco_medio', 'preco_atual']);

            $gump->validation_rules([ /* Regras de atualização ... */]);
            $gump->filter_rules([ /* Filtros ... */]);

            $data = $gump->run($payload);
            if ($data === false) {
                throw new ValidationException($gump->get_errors_array(), 'Falha na validação', 422);
            }

            foreach ($data as $f => $val) {
                // ... (lógica de mapeamento) ...
            }

            $inv->save();

            // ✅ Log de Sucesso
            LogService::info('Investimento atualizado', [
                'user_id' => $uid,
                'investimento_id' => $inv->id
            ]);

            return $inv;
        } catch (ValidationException | \Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            throw $e; // Relança para o controller (422 ou 404)
        } catch (Throwable $e) {
            // 🛑 Log de Erro Inesperado
            LogService::error('Falha ao atualizar investimento no Service', [
                'user_id' => $uid,
                'investimento_id' => $id,
                'exception' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /** Exclui um investimento. */
    public function excluirInvestimento(int $id, int $uid): void
    {
        try {
            $inv = $this->findOrFail($id, $uid);
            $invNome = $inv->nome; // Guarda o nome para o log

            $inv->delete();

            // ✅ Log de Sucesso
            LogService::info('Investimento excluído', [
                'user_id' => $uid,
                'investimento_id' => $id,
                'nome_excluido' => $invNome
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            throw $e; // 404
        } catch (Throwable $e) {
            // 🛑 Log de Erro Inesperado
            LogService::error('Falha ao excluir investimento no Service', [
                'user_id' => $uid,
                'investimento_id' => $id,
                'exception' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /** Atualiza apenas o preço atual. */
    public function atualizarPreco(int $id, int $uid, ?string $precoRaw): Investimento
    {
        try {
            $inv = $this->findOrFail($id, $uid);

            if ($precoRaw === null || $precoRaw === '') {
                throw new ValidationException(['preco_atual' => 'Informe o preço atual']);
            }

            $preco = (float)str_replace(',', '.', $precoRaw);
            if ($preco < 0) {
                throw new ValidationException(['preco_atual' => 'Preço inválido.']);
            }

            $inv->preco_atual = $preco;
            $inv->atualizado_em = date('Y-m-d H:i:s');
            $inv->save();

            // ✅ Log de Sucesso (INFO)
            LogService::info('Preço do investimento atualizado', [
                'user_id' => $uid,
                'investimento_id' => $id,
                'novo_preco' => $preco
            ]);

            return $inv;
        } catch (ValidationException | \Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            throw $e;
        } catch (Throwable $e) {
            LogService::error('Falha ao atualizar preço no Service', [
                'user_id' => $uid,
                'investimento_id' => $id,
                'exception' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    // --- Lógica de Transações e Proventos ---

    public function getCategorias(): \Illuminate\Support\Collection
    {
        try {
            return CategoriaInvestimento::orderBy('nome', 'asc')->get();
        } catch (Throwable $e) {
            LogService::error('Falha ao buscar categorias de investimento', ['exception' => $e->getMessage()]);
            throw $e;
        }
    }

    public function getTransacoes(int $investimentoId, int $uid): \Illuminate\Support\Collection
    {
        try {
            $this->findOrFail($investimentoId, $uid); // Apenas para checar permissão

            return TransacaoInvestimento::where('investimento_id', $investimentoId)
                ->orderBy('data_transacao', 'desc')
                ->orderBy('id', 'desc')
                ->get();
        } catch (Throwable $e) {
            if (!($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException)) {
                LogService::error('Falha ao buscar transações', [
                    'user_id' => $uid,
                    'investimento_id' => $investimentoId,
                    'exception' => $e->getMessage()
                ]);
            }
            throw $e;
        }
    }

    public function criarTransacao(int $investimentoId, int $uid, array $data): TransacaoInvestimento
    {
        try {
            $this->findOrFail($investimentoId, $uid); // Checa permissão

            $gump = new GUMP();
            // ... (Regras de validação GUMP) ...

            $validData = $gump->run($data);
            if ($validData === false) {
                throw new ValidationException($gump->get_errors_array(), 'Falha na validação', 422);
            }

            $tx = new TransacaoInvestimento();
            // ... (Atribuição de dados) ...
            $tx->save(); // O Model deve ter a lógica de atualizar o Investimento (trigger)

            // ✅ Log de Sucesso
            LogService::info('Transação de investimento criada', [
                'user_id' => $uid,
                'investimento_id' => $investimentoId,
                'transacao_id' => $tx->id,
                'tipo' => $tx->tipo,
                'valor' => $tx->preco * $tx->quantidade
            ]);

            return $tx;
        } catch (ValidationException | \Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            throw $e;
        } catch (Throwable $e) {
            // 🛑 Log de Erro Inesperado (DB ou o Trigger do Model)
            LogService::error('Falha ao criar transação no Service', [
                'user_id' => $uid,
                'investimento_id' => $investimentoId,
                'exception' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getProventos(int $investimentoId, int $uid): \Illuminate\Support\Collection
    {
        try {
            $this->findOrFail($investimentoId, $uid); // Checa permissão

            return Provento::where('investimento_id', $investimentoId)
                ->orderBy('data_pagamento', 'desc')
                ->orderBy('id', 'desc')
                ->get();
        } catch (Throwable $e) {
            if (!($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException)) {
                LogService::error('Falha ao buscar proventos', [
                    'user_id' => $uid,
                    'investimento_id' => $investimentoId,
                    'exception' => $e->getMessage()
                ]);
            }
            throw $e;
        }
    }

    public function criarProvento(int $investimentoId, int $uid, array $data): Provento
    {
        try {
            $this->findOrFail($investimentoId, $uid); // Checa permissão

            $gump = new GUMP();
            // ... (Regras de validação GUMP) ...

            $validData = $gump->run($data);
            if ($validData === false) {
                throw new ValidationException($gump->get_errors_array(), 'Falha na validação', 422);
            }

            $p = new Provento();
            // ... (Atribuição de dados) ...
            $p->save();

            // ✅ Log de Sucesso
            LogService::info('Provento criado', [
                'user_id' => $uid,
                'investimento_id' => $investimentoId,
                'provento_id' => $p->id,
                'tipo' => $p->tipo,
                'valor' => $p->valor
            ]);

            return $p;
        } catch (ValidationException | \Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            throw $e;
        } catch (Throwable $e) {
            // 🛑 Log de Erro Inesperado
            LogService::error('Falha ao criar provento no Service', [
                'user_id' => $uid,
                'investimento_id' => $investimentoId,
                'exception' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
