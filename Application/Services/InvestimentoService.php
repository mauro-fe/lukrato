<?php

namespace Application\Services;

use Application\Models\Investimento;
use Application\Models\TransacaoInvestimento;
use Application\Models\Provento;
use Application\Repositories\InvestimentoRepository;
use Application\Core\Exceptions\ValidationException;
use Application\Services\LogService;
use GUMP;
use Illuminate\Support\Collection;
use Throwable;

// --- Enums ---

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
    private InvestimentoRepository $repository;

    public function __construct(?InvestimentoRepository $repository = null)
    {
        $this->repository = $repository ?? new InvestimentoRepository();
    }

    // --- Estatísticas ---

    public function getStats(int $userId): array
    {
        try {
            $investimentos = $this->repository->getAllForUser($userId);

            if ($investimentos->isEmpty()) {
                return $this->buildEmptyStats();
            }

            return $this->calculateAggregatedStats($investimentos);

        } catch (Throwable $e) {
            LogService::error('Falha ao calcular Stats de Investimentos', [
                'user_id' => $userId,
                'exception' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function buildEmptyStats(): array
    {
        return [
            'total_investido' => 0.0,
            'valor_atual' => 0.0,
            'lucro' => 0.0,
            'rentabilidade' => 0.0,
            'quantidade_itens' => 0,
        ];
    }

    private function calculateAggregatedStats(Collection $investimentos): array
    {
        $totals = $investimentos->reduce(function ($carry, Investimento $investimento) {
            $metrics = $this->calcularMetricas($investimento);
            $carry['investido'] += $metrics['valor_investido'];
            $carry['atual'] += $metrics['valor_atual'];
            return $carry;
        }, ['investido' => 0.0, 'atual' => 0.0]);

        $lucro = $totals['atual'] - $totals['investido'];
        $rentabilidade = $totals['investido'] > 0
            ? ($lucro / $totals['investido']) * 100
            : 0.0;

        return [
            'total_investido' => round($totals['investido'], 2),
            'valor_atual' => round($totals['atual'], 2),
            'lucro' => round($lucro, 2),
            'rentabilidade' => round($rentabilidade, 2),
            'quantidade_itens' => $investimentos->count(),
        ];
    }

    // --- Listagem e Detalhes ---

    public function getInvestimentos(int $userId, array $filters): array
    {
        try {
            $investimentos = $this->repository->getFilteredForUser($userId, $filters);

            return $investimentos->map(fn(Investimento $inv) => $this->mapToArray($inv))->all();

        } catch (Throwable $e) {
            LogService::error('Falha ao listar investimentos', [
                'user_id' => $userId,
                'exception' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getInvestimentoById(int $id, int $userId): array
    {
        try {
            $investimento = $this->repository->getByIdAndUser($id, $userId);

            return $this->mapToArray($investimento, includeDetails: true);

        } catch (Throwable $e) {
            if (!($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException)) {
                LogService::error('Falha ao buscar investimento por ID', [
                    'user_id' => $userId,
                    'investimento_id' => $id,
                    'exception' => $e->getMessage()
                ]);
            }
            throw $e;
        }
    }

    // --- CRUD ---

    public function criarInvestimento(int $userId, array $data): Investimento
    {
        try {
            $validData = $this->validateInvestimentoData($data, isCreating: true);

            $investimento = $this->repository->create($userId, $validData);

            LogService::info('Investimento criado', [
                'user_id' => $userId,
                'investimento_id' => $investimento->id,
                'nome' => $investimento->nome
            ]);

            return $investimento;

        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            LogService::error('Falha ao criar investimento', [
                'user_id' => $userId,
                'exception' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function atualizarInvestimento(int $id, int $userId, array $data): Investimento
    {
        try {
            $investimento = $this->repository->getByIdAndUser($id, $userId);

            $validData = $this->validateInvestimentoData($data, isCreating: false);

            $this->applyUpdates($investimento, $validData);

            $this->repository->save($investimento);

            LogService::info('Investimento atualizado', [
                'user_id' => $userId,
                'investimento_id' => $id
            ]);

            return $investimento;

        } catch (ValidationException | \Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            throw $e;
        } catch (Throwable $e) {
            LogService::error('Falha ao atualizar investimento', [
                'user_id' => $userId,
                'investimento_id' => $id,
                'exception' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function excluirInvestimento(int $id, int $userId): void
    {
        try {
            $investimento = $this->repository->getByIdAndUser($id, $userId);
            $nome = $investimento->nome;

            $this->repository->delete($investimento);

            LogService::info('Investimento excluído', [
                'user_id' => $userId,
                'investimento_id' => $id,
                'nome_excluido' => $nome
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            throw $e;
        } catch (Throwable $e) {
            LogService::error('Falha ao excluir investimento', [
                'user_id' => $userId,
                'investimento_id' => $id,
                'exception' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function atualizarPreco(int $id, int $userId, ?string $precoRaw): Investimento
    {
        try {
            $investimento = $this->repository->getByIdAndUser($id, $userId);

            $preco = $this->validateAndParsePreco($precoRaw);

            $investimento->preco_atual = $preco;
            $investimento->atualizado_em = date('Y-m-d H:i:s');

            $this->repository->save($investimento);

            LogService::info('Preço atualizado', [
                'user_id' => $userId,
                'investimento_id' => $id,
                'novo_preco' => $preco
            ]);

            return $investimento;

        } catch (ValidationException | \Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            throw $e;
        } catch (Throwable $e) {
            LogService::error('Falha ao atualizar preço', [
                'user_id' => $userId,
                'investimento_id' => $id,
                'exception' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    // --- Transações ---

    public function getTransacoes(int $investimentoId, int $userId): Collection
    {
        try {
            $this->repository->getByIdAndUser($investimentoId, $userId);

            return $this->repository->getTransacoesByInvestimento($investimentoId);

        } catch (Throwable $e) {
            if (!($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException)) {
                LogService::error('Falha ao buscar transações', [
                    'user_id' => $userId,
                    'investimento_id' => $investimentoId,
                    'exception' => $e->getMessage()
                ]);
            }
            throw $e;
        }
    }

    public function criarTransacao(int $investimentoId, int $userId, array $data): TransacaoInvestimento
    {
        try {
            $this->repository->getByIdAndUser($investimentoId, $userId);

            $validData = $this->validateTransacaoData($data);

            $transacao = $this->repository->createTransacao($investimentoId, $validData);

            LogService::info('Transação criada', [
                'user_id' => $userId,
                'investimento_id' => $investimentoId,
                'transacao_id' => $transacao->id,
                'tipo' => $transacao->tipo
            ]);

            return $transacao;

        } catch (ValidationException | \Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            throw $e;
        } catch (Throwable $e) {
            LogService::error('Falha ao criar transação', [
                'user_id' => $userId,
                'investimento_id' => $investimentoId,
                'exception' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    // --- Proventos ---

    public function getProventos(int $investimentoId, int $userId): Collection
    {
        try {
            $this->repository->getByIdAndUser($investimentoId, $userId);

            return $this->repository->getProventosByInvestimento($investimentoId);

        } catch (Throwable $e) {
            if (!($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException)) {
                LogService::error('Falha ao buscar proventos', [
                    'user_id' => $userId,
                    'investimento_id' => $investimentoId,
                    'exception' => $e->getMessage()
                ]);
            }
            throw $e;
        }
    }

    public function criarProvento(int $investimentoId, int $userId, array $data): Provento
    {
        try {
            $this->repository->getByIdAndUser($investimentoId, $userId);

            $validData = $this->validateProventoData($data);

            $provento = $this->repository->createProvento($investimentoId, $validData);

            LogService::info('Provento criado', [
                'user_id' => $userId,
                'investimento_id' => $investimentoId,
                'provento_id' => $provento->id,
                'valor' => $provento->valor
            ]);

            return $provento;

        } catch (ValidationException | \Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            throw $e;
        } catch (Throwable $e) {
            LogService::error('Falha ao criar provento', [
                'user_id' => $userId,
                'investimento_id' => $investimentoId,
                'exception' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    // --- Categorias ---

    public function getCategorias(): Collection
    {
        try {
            return $this->repository->getCategorias();
        } catch (Throwable $e) {
            LogService::error('Falha ao buscar categorias', [
                'exception' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    // --- Helpers de Cálculo ---

    public function calcularMetricas(Investimento $investimento): array
    {
        $investido = (float)$investimento->quantidade * (float)$investimento->preco_medio;
        $precoAtual = (float)($investimento->preco_atual ?? $investimento->preco_medio ?? 0);
        $atual = (float)$investimento->quantidade * $precoAtual;
        $lucro = $atual - $investido;
        $rentabilidade = $investido > 0 ? ($lucro / $investido) * 100 : 0.0;

        return [
            'valor_investido' => round($investido, 2),
            'valor_atual' => round($atual, 2),
            'lucro' => round($lucro, 2),
            'rentabilidade' => round($rentabilidade, 2),
        ];
    }

    // --- Helpers de Mapeamento ---

    private function mapToArray(Investimento $investimento, bool $includeDetails = false): array
    {
        $base = [
            'id' => (int)$investimento->id,
            'categoria_id' => (int)$investimento->categoria_id,
            'conta_id' => $investimento->conta_id ? (int)$investimento->conta_id : null,
            'nome' => (string)$investimento->nome,
            'ticker' => $investimento->ticker,
            'quantidade' => (float)$investimento->quantidade,
            'preco_medio' => (float)$investimento->preco_medio,
            'preco_atual' => $investimento->preco_atual !== null ? (float)$investimento->preco_atual : null,
            'atualizado_em' => (string)$investimento->atualizado_em,
        ];

        if (!$includeDetails) {
            $base['categoria_nome'] = $investimento->categoria?->nome ?? 'Sem categoria';
            $base['cor'] = $investimento->categoria?->cor ?? '#475569';
        }

        if ($includeDetails) {
            $base['observacoes'] = $investimento->observacoes;
        }

        return array_merge($base, $this->calcularMetricas($investimento));
    }

    // --- Helpers de Validação ---

    private function validateInvestimentoData(array $data, bool $isCreating): array
    {
        $this->normalizeNumerics($data, ['quantidade', 'preco_medio', 'preco_atual']);

        $gump = new GUMP();

        $rules = [
            'categoria_id' => 'required|integer|min_numeric,1',
            'nome' => 'required|max_len,255',
            'quantidade' => 'required|numeric|min_numeric,0.00000001',
            'preco_medio' => 'required|numeric|min_numeric,0.01',
        ];

        if ($isCreating) {
            $rules['preco_atual'] = 'numeric|min_numeric,0';
        }

        $gump->validation_rules($rules);

        $gump->filter_rules([
            'nome' => 'trim|sanitize_string',
            'ticker' => 'trim|upper_case',
            'observacoes' => 'trim',
        ]);

        $validData = $gump->run($data);

        if ($validData === false) {
            throw new ValidationException($gump->get_errors_array(), 'Falha na validação', 422);
        }

        return $validData;
    }

    private function validateTransacaoData(array $data): array
    {
        $this->normalizeNumerics($data, ['quantidade', 'preco', 'taxas']);

        $gump = new GUMP();

        $gump->validation_rules([
            'tipo' => 'required|contains_list,' . TransacaoTipo::listValues(),
            'quantidade' => 'required|numeric|min_numeric,0.00000001',
            'preco' => 'required|numeric|min_numeric,0.01',
            'taxas' => 'numeric|min_numeric,0',
            'data_transacao' => 'required|date',
        ]);

        $gump->filter_rules([
            'observacoes' => 'trim',
        ]);

        $validData = $gump->run($data);

        if ($validData === false) {
            throw new ValidationException($gump->get_errors_array(), 'Falha na validação', 422);
        }

        return $validData;
    }

    private function validateProventoData(array $data): array
    {
        $this->normalizeNumerics($data, ['valor']);

        $gump = new GUMP();

        $gump->validation_rules([
            'valor' => 'required|numeric|min_numeric,0.01',
            'tipo' => 'required|contains_list,' . ProventoTipo::listValues(),
            'data_pagamento' => 'required|date',
        ]);

        $gump->filter_rules([
            'observacoes' => 'trim',
        ]);

        $validData = $gump->run($data);

        if ($validData === false) {
            throw new ValidationException($gump->get_errors_array(), 'Falha na validação', 422);
        }

        return $validData;
    }

    private function validateAndParsePreco(?string $precoRaw): float
    {
        if ($precoRaw === null || $precoRaw === '') {
            throw new ValidationException(['preco_atual' => 'Informe o preço atual']);
        }

        $preco = (float)str_replace(',', '.', $precoRaw);

        if ($preco < 0) {
            throw new ValidationException(['preco_atual' => 'Preço inválido']);
        }

        return $preco;
    }

    private function applyUpdates(Investimento $investimento, array $validData): void
    {
        $allowedFields = [
            'categoria_id', 'conta_id', 'nome', 'ticker',
            'quantidade', 'preco_medio', 'preco_atual',
            'data_compra', 'observacoes'
        ];

        foreach ($validData as $field => $value) {
            if (in_array($field, $allowedFields, true)) {
                $investimento->{$field} = $value;
            }
        }
    }

    private function normalizeNumerics(array &$data, array $keys): void
    {
        foreach ($keys as $key) {
            if (isset($data[$key]) && $data[$key] !== '') {
                $data[$key] = str_replace(',', '.', (string)$data[$key]);
            }
        }
    }
}