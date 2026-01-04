<?php

declare(strict_types=1);

namespace Application\Repositories;

use Application\Models\Lancamento;
use Application\Enums\LancamentoTipo;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Repository para operações com lançamentos.
 */
class LancamentoRepository extends BaseRepository
{
    /**
     * {@inheritdoc}
     */
    protected function getModelClass(): string
    {
        return Lancamento::class;
    }

    /**
     * Busca lançamentos de um usuário específico.
     * 
     * @param int $userId
     * @return Collection
     */
    public function findByUser(int $userId): Collection
    {
        return $this->query()
            ->where('user_id', $userId)
            ->orderBy('data', 'desc')
            ->orderBy('id', 'desc')
            ->get();
    }

    /**
     * Busca lançamentos de um usuário em um mês específico.
     * 
     * @param int $userId
     * @param string $month Formato: Y-m (ex: 2025-12)
     * @return Collection
     */
    public function findByUserAndMonth(int $userId, string $month): Collection
    {
        [$year, $monthNum] = explode('-', $month);

        return $this->query()
            ->where('user_id', $userId)
            ->whereYear('data', (int)$year)
            ->whereMonth('data', (int)$monthNum)
            ->orderBy('data', 'desc')
            ->orderBy('id', 'desc')
            ->get();
    }

    /**
     * Busca lançamentos por período.
     * 
     * @param int $userId
     * @param string $startDate
     * @param string $endDate
     * @return Collection
     */
    public function findByPeriod(int $userId, string $startDate, string $endDate): Collection
    {
        return $this->query()
            ->where('user_id', $userId)
            ->whereBetween('data', [$startDate, $endDate])
            ->orderBy('data', 'desc')
            ->orderBy('id', 'desc')
            ->get();
    }

    /**
     * Busca lançamentos por conta.
     * 
     * @param int $userId
     * @param int $contaId
     * @return Collection
     */
    public function findByAccount(int $userId, int $contaId): Collection
    {
        return $this->query()
            ->where('user_id', $userId)
            ->where(function ($query) use ($contaId) {
                $query->where('conta_id', $contaId)
                    ->orWhere('conta_id_destino', $contaId);
            })
            ->orderBy('data', 'desc')
            ->get();
    }

    /**
     * Busca lançamentos por categoria.
     * 
     * @param int $userId
     * @param int $categoriaId
     * @return Collection
     */
    public function findByCategory(int $userId, int $categoriaId): Collection
    {
        return $this->query()
            ->where('user_id', $userId)
            ->where('categoria_id', $categoriaId)
            ->orderBy('data', 'desc')
            ->get();
    }

    /**
     * Busca lançamentos por tipo (receita/despesa).
     * 
     * @param int $userId
     * @param LancamentoTipo $tipo
     * @return Collection
     */
    public function findByType(int $userId, LancamentoTipo $tipo): Collection
    {
        return $this->query()
            ->where('user_id', $userId)
            ->where('tipo', $tipo->value)
            ->orderBy('data', 'desc')
            ->get();
    }

    /**
     * Busca um lançamento específico de um usuário.
     * 
     * @param int $id
     * @param int $userId
     * @return Lancamento|null
     */
    public function findByIdAndUser(int $id, int $userId): ?Lancamento
    {
        return $this->query()
            ->where('id', $id)
            ->where('user_id', $userId)
            ->first();
    }

    /**
     * Busca um lançamento específico de um usuário ou lança exceção.
     * 
     * @param int $id
     * @param int $userId
     * @return Lancamento
     * @throws ModelNotFoundException
     */
    public function findByIdAndUserOrFail(int $id, int $userId): Lancamento
    {
        $lancamento = $this->findByIdAndUser($id, $userId);

        if (!$lancamento) {
            throw new ModelNotFoundException('Lançamento não encontrado');
        }

        return $lancamento;
    }

    /**
     * Conta lançamentos de um usuário em um mês.
     * 
     * @param int $userId
     * @param string $month Formato: Y-m
     * @param bool $excludeTransfers Excluir transferências
     * @return int
     */
    public function countByMonth(
        int $userId,
        string $month,
        bool $excludeTransfers = true
    ): int {
        [$year, $monthNum] = explode('-', $month);

        $query = $this->query()
            ->where('user_id', $userId)
            ->whereYear('data', (int)$year)
            ->whereMonth('data', (int)$monthNum);

        if ($excludeTransfers) {
            // Excluir transferências e lançamentos de saldo inicial (não contam como lançamentos do mês)
            $query->where('eh_transferencia', 0)
                ->where('eh_saldo_inicial', 0);
        }

        return $query->count();
    }

    /**
     * Busca apenas receitas de um usuário.
     * 
     * @param int $userId
     * @param bool $excludeTransfers
     * @return Collection
     */
    public function findReceitas(int $userId, bool $excludeTransfers = true): Collection
    {
        $query = $this->query()
            ->where('user_id', $userId)
            ->where('tipo', LancamentoTipo::RECEITA->value);

        if ($excludeTransfers) {
            $query->where('eh_transferencia', 0);
        }

        return $query->orderBy('data', 'desc')->get();
    }

    /**
     * Busca apenas despesas de um usuário.
     * 
     * @param int $userId
     * @param bool $excludeTransfers
     * @return Collection
     */
    public function findDespesas(int $userId, bool $excludeTransfers = true): Collection
    {
        $query = $this->query()
            ->where('user_id', $userId)
            ->where('tipo', LancamentoTipo::DESPESA->value);

        if ($excludeTransfers) {
            $query->where('eh_transferencia', 0);
        }

        return $query->orderBy('data', 'desc')->get();
    }

    /**
     * Busca apenas transferências de um usuário.
     * 
     * @param int $userId
     * @return Collection
     */
    public function findTransferencias(int $userId): Collection
    {
        return $this->query()
            ->where('user_id', $userId)
            ->where('eh_transferencia', 1)
            ->orderBy('data', 'desc')
            ->get();
    }

    /**
     * Calcula soma de valores por tipo em um período.
     * 
     * @param int $userId
     * @param string $startDate
     * @param string $endDate
     * @param LancamentoTipo $tipo
     * @param bool $excludeTransfers
     * @return float
     */
    public function sumByTypeAndPeriod(
        int $userId,
        string $startDate,
        string $endDate,
        LancamentoTipo $tipo,
        bool $excludeTransfers = true
    ): float {
        $query = $this->query()
            ->where('user_id', $userId)
            ->where('tipo', $tipo->value)
            ->whereBetween('data', [$startDate, $endDate]);

        if ($excludeTransfers) {
            $query->where('eh_transferencia', 0);
        }

        return (float) $query->sum('valor');
    }

    /**
     * Deleta todos os lançamentos de uma conta.
     * 
     * @param int $userId
     * @param int $contaId
     * @return int Número de registros deletados
     */
    public function deleteByAccount(int $userId, int $contaId): int
    {
        return $this->query()
            ->where('user_id', $userId)
            ->where(function ($query) use ($contaId) {
                $query->where('conta_id', $contaId)
                    ->orWhere('conta_id_destino', $contaId);
            })
            ->delete();
    }

    /**     * Atualiza um lançamento com lógica especial para cartões de crédito.
     * 
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $lancamento = $this->findOrFail($id);

        // Verificar se é um lançamento de pagamento de fatura de cartão
        // e se está alterando o status de pago
        if (
            isset($data['pago']) &&
            $lancamento->cartao_credito_id !== null &&
            $lancamento->tipo === 'despesa' &&
            strpos($lancamento->descricao, 'Pagamento Fatura') !== false
        ) {

            $pagoAntigo = (bool) $lancamento->pago;
            $pagoNovo = (bool) $data['pago'];

            // Se está desmarcando como pago (estava pago e agora não está)
            if ($pagoAntigo && !$pagoNovo) {
                // Reverter o débito da conta - reduzir o limite disponível do cartão
                $cartao = $lancamento->cartaoCredito;
                if ($cartao) {
                    // Reduz o limite disponível (pois a fatura não foi paga)
                    $cartao->limite_disponivel -= $lancamento->valor;
                    $cartao->save();
                }

                // Desmarcar as parcelas da fatura como não pagas
                $this->desmarcarParcelasFatura($lancamento);

                // DELETAR o lançamento de pagamento para reverter o débito da conta
                // Isso faz o saldo voltar ao valor anterior
                $lancamento->delete();

                // Retornar true pois a operação foi bem-sucedida (deletou)
                return true;
            }
            // Se está marcando como pago (não estava pago e agora está)
            elseif (!$pagoAntigo && $pagoNovo) {
                // Devolver o limite ao cartão
                $cartao = $lancamento->cartaoCredito;
                if ($cartao) {
                    $cartao->limite_disponivel += $lancamento->valor;
                    $cartao->save();
                }
            }
        }

        return $lancamento->update($data);
    }

    /**
     * Desmarca as parcelas de uma fatura como não pagas
     * quando o pagamento da fatura é desmarcado
     */
    private function desmarcarParcelasFatura(Lancamento $pagamentoFatura): void
    {
        // Extrair mês/ano da descrição do pagamento
        // Formato: "Pagamento Fatura NOME •••• DIGITOS - MM/YYYY"
        if (preg_match('/- (\d{2})\/(\d{4})/', $pagamentoFatura->descricao, $matches)) {
            $mes = (int) $matches[1];
            $ano = (int) $matches[2];

            // Buscar lançamentos do cartão naquele mês que foram marcados como pagos
            $dataInicio = sprintf('%04d-%02d-01', $ano, $mes);
            $dataFim = date('Y-m-t', strtotime($dataInicio));

            $this->query()
                ->where('user_id', $pagamentoFatura->user_id)
                ->where('cartao_credito_id', $pagamentoFatura->cartao_credito_id)
                ->whereBetween('data', [$dataInicio, $dataFim])
                ->where('pago', true)
                ->update([
                    'pago' => false,
                    'data_pagamento' => null
                ]);
        }
    }

    /**     * Atualiza categoria de múltiplos lançamentos.
     * 
     * @param int $userId
     * @param int $oldCategoryId
     * @param int|null $newCategoryId
     * @return int Número de registros atualizados
     */
    public function updateCategory(int $userId, int $oldCategoryId, ?int $newCategoryId): int
    {
        return $this->query()
            ->where('user_id', $userId)
            ->where('categoria_id', $oldCategoryId)
            ->update(['categoria_id' => $newCategoryId]);
    }

    /**
     * Verifica se existe lançamento não-transferência em uma conta.
     * 
     * @param int $userId
     * @param int $contaId
     * @return bool
     */
    public function hasNonTransferLancamentos(int $userId, int $contaId): bool
    {
        return $this->query()
            ->where('user_id', $userId)
            ->where('conta_id', $contaId)
            ->where('eh_transferencia', 0)
            ->exists();
    }
}
