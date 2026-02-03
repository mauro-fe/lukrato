<?php

declare(strict_types=1);

namespace Application\Repositories;

use Application\Models\Lancamento;
use Application\Models\FaturaCartaoItem;
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
     * Deleta um lançamento e seus itens de fatura vinculados.
     * 
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $lancamento = $this->findOrFail($id);

        // Buscar itens de fatura vinculados a este lançamento ANTES de excluir
        $itensVinculados = FaturaCartaoItem::where('lancamento_id', $id)->get();

        // Coletar IDs de faturas afetadas e cartões para atualizar depois
        $faturaIds = $itensVinculados->pluck('fatura_id')->unique()->filter()->values();
        $cartaoIds = $itensVinculados->pluck('cartao_credito_id')->unique()->filter()->values();

        // Excluir itens de fatura vinculados a este lançamento
        FaturaCartaoItem::where('lancamento_id', $id)->delete();

        // Atualizar faturas afetadas
        foreach ($faturaIds as $faturaId) {
            $fatura = \Application\Models\Fatura::find($faturaId);
            if ($fatura) {
                // Verificar se ainda tem itens
                $itensRestantes = FaturaCartaoItem::where('fatura_id', $faturaId)->count();

                if ($itensRestantes === 0) {
                    // Se não tem mais itens, excluir a fatura também
                    $fatura->delete();
                    error_log("🗑️ [DELETE] Fatura {$faturaId} excluída (sem itens restantes)");
                } else {
                    // Atualizar valor total e status
                    $novoTotal = FaturaCartaoItem::where('fatura_id', $faturaId)->sum('valor');
                    $fatura->valor_total = $novoTotal;
                    $fatura->save();
                    $fatura->atualizarStatus();
                    error_log("📊 [DELETE] Fatura {$faturaId} atualizada - Novo total: {$novoTotal}, Status: {$fatura->status}");
                }
            }
        }

        // Atualizar limite dos cartões afetados
        foreach ($cartaoIds as $cartaoId) {
            $cartao = \Application\Models\CartaoCredito::find($cartaoId);
            if ($cartao) {
                $cartao->atualizarLimiteDisponivel();
                error_log("💳 [DELETE] Limite do cartão {$cartaoId} recalculado");
            }
        }

        return $lancamento->delete();
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

    // ============================================================================
    // MÉTODOS DE COMPETÊNCIA (Refatoração Cartão de Crédito)
    // ============================================================================

    /**
     * Buscar lançamentos por mês usando competência ou caixa.
     * 
     * @param int $userId
     * @param string $month Formato: Y-m (ex: 2025-12)
     * @param string $tipo 'competencia' ou 'caixa'
     * @return Collection
     */
    public function findByMonthAndViewType(int $userId, string $month, string $tipo = 'caixa'): Collection
    {
        $query = $this->query()
            ->where('user_id', $userId)
            ->where('eh_transferencia', 0);

        if ($tipo === 'competencia') {
            // Usar data_competencia se disponível, senão fallback para data
            $query->where(function ($q) use ($month) {
                $q->where('data_competencia', 'like', "$month%")
                    ->orWhere(function ($q2) use ($month) {
                        $q2->whereNull('data_competencia')
                            ->where('data', 'like', "$month%");
                    });
            });
        } else {
            // Comportamento original: fluxo de caixa (usa campo data)
            $query->where('data', 'like', "$month%");
        }

        return $query->orderBy('data', 'desc')
            ->orderBy('id', 'desc')
            ->get();
    }

    /**
     * Calcular soma de receitas por competência.
     * Usa data_competencia se disponível, senão usa data.
     * 
     * @param int $userId
     * @param string $start Data inicial (Y-m-d)
     * @param string $end Data final (Y-m-d)
     * @return float
     */
    public function sumReceitasCompetencia(int $userId, string $start, string $end): float
    {
        return (float) $this->query()
            ->where('user_id', $userId)
            ->where('tipo', LancamentoTipo::RECEITA->value)
            ->where('eh_transferencia', 0)
            ->where(function ($q) {
                $q->where('afeta_competencia', true)
                    ->orWhereNull('afeta_competencia'); // Backward compatibility
            })
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('data_competencia', [$start, $end])
                    ->orWhere(function ($q2) use ($start, $end) {
                        $q2->whereNull('data_competencia')
                            ->whereBetween('data', [$start, $end]);
                    });
            })
            ->sum('valor');
    }

    /**
     * Calcular soma de despesas por competência.
     * Usa data_competencia se disponível, senão usa data.
     * 
     * @param int $userId
     * @param string $start Data inicial (Y-m-d)
     * @param string $end Data final (Y-m-d)
     * @return float
     */
    public function sumDespesasCompetencia(int $userId, string $start, string $end): float
    {
        return (float) $this->query()
            ->where('user_id', $userId)
            ->where('tipo', LancamentoTipo::DESPESA->value)
            ->where('eh_transferencia', 0)
            ->where(function ($q) {
                $q->where('afeta_competencia', true)
                    ->orWhereNull('afeta_competencia'); // Backward compatibility
            })
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('data_competencia', [$start, $end])
                    ->orWhere(function ($q2) use ($start, $end) {
                        $q2->whereNull('data_competencia')
                            ->whereBetween('data', [$start, $end]);
                    });
            })
            ->sum('valor');
    }

    /**
     * Calcular soma de receitas por caixa (fluxo de caixa).
     * Sempre usa campo data.
     * 
     * @param int $userId
     * @param string $start Data inicial (Y-m-d)
     * @param string $end Data final (Y-m-d)
     * @return float
     */
    public function sumReceitasCaixa(int $userId, string $start, string $end): float
    {
        return (float) $this->query()
            ->where('user_id', $userId)
            ->where('tipo', LancamentoTipo::RECEITA->value)
            ->where('eh_transferencia', 0)
            ->where(function ($q) {
                $q->where('afeta_caixa', true)
                    ->orWhereNull('afeta_caixa'); // Backward compatibility
            })
            ->whereBetween('data', [$start, $end])
            ->sum('valor');
    }

    /**
     * Calcular soma de despesas por caixa (fluxo de caixa).
     * Sempre usa campo data.
     * 
     * @param int $userId
     * @param string $start Data inicial (Y-m-d)
     * @param string $end Data final (Y-m-d)
     * @return float
     */
    public function sumDespesasCaixa(int $userId, string $start, string $end): float
    {
        return (float) $this->query()
            ->where('user_id', $userId)
            ->where('tipo', LancamentoTipo::DESPESA->value)
            ->where('eh_transferencia', 0)
            ->where(function ($q) {
                $q->where('afeta_caixa', true)
                    ->orWhereNull('afeta_caixa'); // Backward compatibility
            })
            ->whereBetween('data', [$start, $end])
            ->sum('valor');
    }

    /**
     * Buscar lançamentos de cartão de crédito de um usuário.
     * 
     * @param int $userId
     * @param string|null $month Formato: Y-m (opcional)
     * @return Collection
     */
    public function findCartaoCredito(int $userId, ?string $month = null): Collection
    {
        $query = $this->query()
            ->where('user_id', $userId)
            ->where(function ($q) {
                $q->where('origem_tipo', Lancamento::ORIGEM_CARTAO_CREDITO)
                    ->orWhereNotNull('cartao_credito_id');
            });

        if ($month) {
            $query->where(function ($q) use ($month) {
                $q->where('data_competencia', 'like', "$month%")
                    ->orWhere(function ($q2) use ($month) {
                        $q2->whereNull('data_competencia')
                            ->where('data', 'like', "$month%");
                    });
            });
        }

        return $query->orderBy('data', 'desc')
            ->orderBy('id', 'desc')
            ->get();
    }

    /**
     * Obter resumo mensal por competência vs caixa.
     * Útil para comparar os dois métodos de visualização.
     * 
     * @param int $userId
     * @param string $month Formato: Y-m
     * @return array
     */
    public function getResumoCompetenciaVsCaixa(int $userId, string $month): array
    {
        [$year, $monthNum] = explode('-', $month);
        $start = "{$year}-{$monthNum}-01";
        $end = date('Y-m-t', strtotime($start));

        return [
            'competencia' => [
                'receitas' => $this->sumReceitasCompetencia($userId, $start, $end),
                'despesas' => $this->sumDespesasCompetencia($userId, $start, $end),
            ],
            'caixa' => [
                'receitas' => $this->sumReceitasCaixa($userId, $start, $end),
                'despesas' => $this->sumDespesasCaixa($userId, $start, $end),
            ],
        ];
    }
}
