<?php

namespace Application\Repositories;

use Application\Models\Investimento;
use Application\Models\TransacaoInvestimento;
use Application\Models\Provento;
use Application\Models\CategoriaInvestimento;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class InvestimentoRepository
{
    // --- Busca de Investimentos ---

    /**
     * Busca um investimento pelo ID e usuário, ou lança exceção.
     *
     * @throws ModelNotFoundException
     */
    public function getByIdAndUser(int $id, int $userId): Investimento
    {
        $investimento = Investimento::where('user_id', $userId)->find($id);

        if (!$investimento) {
            throw new ModelNotFoundException('Investimento não encontrado');
        }

        return $investimento;
    }

    /**
     * Retorna todos os investimentos de um usuário.
     */
    public function getAllForUser(int $userId): Collection
    {
        return Investimento::where('user_id', $userId)->get();
    }

    /**
     * Lista investimentos com filtros aplicados.
     */
    public function getFilteredForUser(int $userId, array $filters): Collection
    {
        $query = $this->buildFilteredQuery($userId, $filters);

        return $query->get();
    }

    // --- CRUD de Investimentos ---

    /**
     * Cria um novo investimento.
     */
    public function create(int $userId, array $validData): Investimento
    {
        $investimento = new Investimento();

        $this->fillInvestimento($investimento, $userId, $validData);

        $investimento->save();

        return $investimento;
    }

    /**
     * Salva as alterações de um investimento.
     */
    public function save(Investimento $investimento): bool
    {
        return $investimento->save();
    }

    /**
     * Exclui um investimento.
     */
    public function delete(Investimento $investimento): bool
    {
        return $investimento->delete();
    }

    // --- Categorias ---

    /**
     * Retorna todas as categorias de investimento.
     */
    public function getCategorias(): Collection
    {
        return CategoriaInvestimento::orderBy('nome', 'asc')->get();
    }

    // --- Transações ---

    /**
     * Retorna todas as transações de um investimento.
     */
    public function getTransacoesByInvestimento(int $investimentoId): Collection
    {
        return TransacaoInvestimento::where('investimento_id', $investimentoId)
            ->orderBy('data_transacao', 'desc')
            ->orderBy('id', 'desc')
            ->get();
    }

    /**
     * Cria uma nova transação.
     */
    public function createTransacao(int $investimentoId, array $validData): TransacaoInvestimento
    {
        $transacao = new TransacaoInvestimento();

        $this->fillTransacao($transacao, $investimentoId, $validData);

        $transacao->save();

        return $transacao;
    }

    // --- Proventos ---

    /**
     * Retorna todos os proventos de um investimento.
     */
    public function getProventosByInvestimento(int $investimentoId): Collection
    {
        return Provento::where('investimento_id', $investimentoId)
            ->orderBy('data_pagamento', 'desc')
            ->orderBy('id', 'desc')
            ->get();
    }

    /**
     * Cria um novo provento.
     */
    public function createProvento(int $investimentoId, array $validData): Provento
    {
        $provento = new Provento();

        $this->fillProvento($provento, $investimentoId, $validData);

        $provento->save();

        return $provento;
    }

    // --- Helpers de Query ---

    private function buildFilteredQuery(int $userId, array $filters)
    {
        $query = Investimento::with('categoria')->where('user_id', $userId);

        $this->applySearchFilter($query, $filters);
        $this->applyCategoryFilter($query, $filters);
        $this->applyAccountFilter($query, $filters);
        $this->applyTickerFilter($query, $filters);
        $this->applyOrdering($query, $filters);

        return $query;
    }

    private function applySearchFilter($query, array $filters): void
    {
        $searchTerm = trim((string)($filters['q'] ?? ''));

        if ($searchTerm === '') {
            return;
        }

        $query->where(function ($q) use ($searchTerm) {
            $q->where('nome', 'like', "%{$searchTerm}%")
                ->orWhere('ticker', 'like', "%{$searchTerm}%");
        });
    }

    private function applyCategoryFilter($query, array $filters): void
    {
        $categoriaId = (int)($filters['categoria_id'] ?? 0);

        if ($categoriaId > 0) {
            $query->where('categoria_id', $categoriaId);
        }
    }

    private function applyAccountFilter($query, array $filters): void
    {
        $contaId = (int)($filters['conta_id'] ?? 0);

        if ($contaId > 0) {
            $query->where('conta_id', $contaId);
        }
    }

    private function applyTickerFilter($query, array $filters): void
    {
        $ticker = trim((string)($filters['ticker'] ?? ''));

        if ($ticker !== '') {
            $query->where('ticker', $ticker);
        }
    }

    private function applyOrdering($query, array $filters): void
    {
        $order = $filters['order'] ?? 'nome';
        $direction = strtolower($filters['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        $allowedColumns = ['nome', 'ticker', 'quantidade', 'preco_medio', 'preco_atual', 'atualizado_em'];

        if (in_array($order, $allowedColumns, true)) {
            $query->orderBy($order, $direction);
        } else {
            $query->orderBy('nome', 'asc');
        }
    }

    // --- Helpers de Preenchimento ---

    private function fillInvestimento(Investimento $investimento, int $userId, array $data): void
    {
        $investimento->user_id = $userId;
        $investimento->categoria_id = (int)$data['categoria_id'];
        $investimento->conta_id = !empty($data['conta_id']) ? (int)$data['conta_id'] : null;
        $investimento->nome = (string)$data['nome'];
        $investimento->ticker = $data['ticker'] ?: null;
        $investimento->quantidade = (float)$data['quantidade'];
        $investimento->preco_medio = (float)$data['preco_medio'];
        $investimento->preco_atual = $this->parseOptionalFloat($data['preco_atual'] ?? null);
        $investimento->data_compra = !empty($data['data_compra']) ? $data['data_compra'] : null;
        $investimento->observacoes = $data['observacoes'] ?: null;
    }

    private function fillTransacao(TransacaoInvestimento $transacao, int $investimentoId, array $data): void
    {
        $transacao->investimento_id = $investimentoId;
        $transacao->tipo = $data['tipo'];
        $transacao->quantidade = (float)$data['quantidade'];
        $transacao->preco = (float)$data['preco'];
        $transacao->taxas = (float)($data['taxas'] ?? 0.0);
        $transacao->data_transacao = $data['data_transacao'];
        $transacao->observacoes = $data['observacoes'] ?: null;
    }

    private function fillProvento(Provento $provento, int $investimentoId, array $data): void
    {
        $provento->investimento_id = $investimentoId;
        $provento->valor = (float)$data['valor'];
        $provento->tipo = $data['tipo'];
        $provento->data_pagamento = $data['data_pagamento'];
        $provento->observacoes = $data['observacoes'] ?: null;
    }

    // --- Helpers Utilitários ---

    private function parseOptionalFloat($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float)$value;
    }
}