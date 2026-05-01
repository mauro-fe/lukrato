<?php

declare(strict_types=1);

namespace Application\Repositories;

use Application\Models\Lancamento;
use Application\Models\Conta;
use Application\Models\FaturaCartaoItem;
use Application\Enums\LancamentoTipo;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;

/**
 * Repository para operações com lançamentos.
 *
 * @extends BaseRepository<Lancamento>
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
            ->with(['categoria', 'conta', 'cartaoCredito'])
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
            ->with(['categoria', 'conta', 'cartaoCredito'])
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
            ->with(['categoria', 'conta', 'cartaoCredito'])
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
            ->with(['categoria', 'conta', 'cartaoCredito'])
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
            ->with(['categoria', 'conta', 'cartaoCredito'])
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
            ->with(['categoria', 'conta', 'cartaoCredito'])
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
                    \Application\Services\Infrastructure\LogService::safeErrorLog("🗑️ [DELETE] Fatura {$faturaId} excluída (sem itens restantes)");
                } else {
                    // Atualizar valor total e status
                    $novoTotal = FaturaCartaoItem::where('fatura_id', $faturaId)->sum('valor');
                    $fatura->valor_total = $novoTotal;
                    $fatura->save();
                    $fatura->atualizarStatus();
                    \Application\Services\Infrastructure\LogService::safeErrorLog("📊 [DELETE] Fatura {$faturaId} atualizada - Novo total: {$novoTotal}, Status: {$fatura->status}");
                }
            }
        }

        // Atualizar limite dos cartões afetados
        foreach ($cartaoIds as $cartaoId) {
            $cartao = \Application\Models\CartaoCredito::find($cartaoId);
            if ($cartao) {
                $cartao->atualizarLimiteDisponivel();
                \Application\Services\Infrastructure\LogService::safeErrorLog("💳 [DELETE] Limite do cartão {$cartaoId} recalculado");
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

    /**
     * Busca lançamentos por filtros combinados (para listagem).
     *
     * Carrega eager-load das relações categoria, conta e cartaoCredito.
     *
     * @param int $userId
     * @param string $startDate Data inicial (Y-m-d)
     * @param string $endDate Data final (Y-m-d)
     * @param array{
     *     account_id?: int|null,
     *     categoria_id?: int|null,
     *     categoria_null?: bool,
     *     tipo?: string|null,
     *     status?: string|null,
     *     search?: string|null,
     *     limit?: int
     * } $filters
     * @return Collection
     */
    public function findByFilters(int $userId, string $startDate, string $endDate, array $filters = []): Collection
    {
        $limit = min((int) ($filters['limit'] ?? 500), 1000);
        $search = trim((string) ($filters['search'] ?? ''));

        return $this->query()
            ->with(['categoria', 'subcategoria', 'conta', 'cartaoCredito', 'parcelamento:id,numero_parcelas,parcelas_pagas,status'])
            ->where('user_id', $userId)
            ->whereBetween('data', [$startDate, $endDate])
            ->when($filters['account_id'] ?? null, function ($q, $accId) {
                $q->where(function ($s) use ($accId) {
                    $s->where('conta_id', $accId)
                        ->orWhere('conta_id_destino', $accId);
                });
            })
            ->when($filters['categoria_null'] ?? false, fn($q) => $q->whereNull('categoria_id'))
            ->when($filters['categoria_id'] ?? null, fn($q, $catId) => $q->where('categoria_id', $catId))
            ->when($filters['tipo'] ?? null, fn($q, $tipo) => $q->where('tipo', $tipo))
            ->when($filters['status'] ?? null, function ($q, $status) {
                if ($status === 'pago') {
                    $q->where(function ($s) {
                        $s->where('pago', 1)
                            ->orWhere('tipo', LancamentoTipo::TRANSFERENCIA->value)
                            ->orWhere('eh_transferencia', 1);
                    });
                    return;
                }

                if ($status === 'pendente') {
                    $q->where(function ($s) {
                        $s->whereNull('pago')->orWhere('pago', 0);
                    })->where('tipo', '!=', LancamentoTipo::TRANSFERENCIA->value)
                        ->where(function ($s) {
                            $s->whereNull('eh_transferencia')->orWhere('eh_transferencia', 0);
                        });
                }
            })
            ->when($search !== '', function ($q) use ($search) {
                $term = '%' . addcslashes($search, '%_\\') . '%';

                $q->where(function ($s) use ($term) {
                    $s->where('descricao', 'like', $term)
                        ->orWhereHas('categoria', fn($cat) => $cat->where('nome', 'like', $term))
                        ->orWhereHas('subcategoria', fn($sub) => $sub->where('nome', 'like', $term))
                        ->orWhereHas('conta', function ($conta) use ($term) {
                            $conta->where(function ($c) use ($term) {
                                $c->where('nome', 'like', $term)
                                    ->orWhere('instituicao', 'like', $term);
                            });
                        })
                        ->orWhereHas('contaDestino', function ($conta) use ($term) {
                            $conta->where(function ($c) use ($term) {
                                $c->where('nome', 'like', $term)
                                    ->orWhere('instituicao', 'like', $term);
                            });
                        });
                });
            })
            ->orderBy('data', 'desc')
            ->orderBy('id', 'desc')
            ->limit($limit)
            ->get();
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
            ->where('pago', 1)
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
        $total = $this->query()
            ->where('user_id', $userId)
            ->where('pago', 1)
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
            ->selectRaw('SUM(' . $this->effectiveExpenseExpression('lancamentos') . ') as total')
            ->value('total');

        return (float) ($total ?? 0);
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
            ->where('pago', 1)
            ->where('tipo', LancamentoTipo::RECEITA->value)
            ->where('eh_transferencia', 0)
            ->where('afeta_caixa', 1)
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
        $total = $this->query()
            ->where('user_id', $userId)
            ->where('pago', 1)
            ->where('tipo', LancamentoTipo::DESPESA->value)
            ->where('eh_transferencia', 0)
            ->where('afeta_caixa', 1)
            ->whereBetween('data', [$start, $end])
            ->selectRaw('SUM(' . $this->effectiveExpenseExpression('lancamentos') . ') as total')
            ->value('total');

        return (float) ($total ?? 0);
    }

    public function sumDespesasBrutasCompetencia(int $userId, string $start, string $end): float
    {
        return (float) $this->query()
            ->where('user_id', $userId)
            ->where('pago', 1)
            ->where('tipo', LancamentoTipo::DESPESA->value)
            ->where('eh_transferencia', 0)
            ->where(function ($q) {
                $q->where('afeta_competencia', true)
                    ->orWhereNull('afeta_competencia');
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

    public function sumDespesasBrutasCaixa(int $userId, string $start, string $end): float
    {
        return (float) $this->query()
            ->where('user_id', $userId)
            ->where('pago', 1)
            ->where('tipo', LancamentoTipo::DESPESA->value)
            ->where('eh_transferencia', 0)
            ->where('afeta_caixa', 1)
            ->whereBetween('data', [$start, $end])
            ->sum('valor');
    }

    public function sumUsoMetasDespesaCompetencia(int $userId, string $start, string $end): float
    {
        $total = $this->query()
            ->where('user_id', $userId)
            ->where('pago', 1)
            ->where('tipo', LancamentoTipo::DESPESA->value)
            ->where('eh_transferencia', 0)
            ->where(function ($q) {
                $q->where('afeta_competencia', true)
                    ->orWhereNull('afeta_competencia');
            })
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('data_competencia', [$start, $end])
                    ->orWhere(function ($q2) use ($start, $end) {
                        $q2->whereNull('data_competencia')
                            ->whereBetween('data', [$start, $end]);
                    });
            })
            ->selectRaw('SUM(' . $this->metaCoverageExpression('lancamentos') . ') as total')
            ->value('total');

        return (float) ($total ?? 0);
    }

    public function sumUsoMetasDespesaCaixa(int $userId, string $start, string $end): float
    {
        $total = $this->query()
            ->where('user_id', $userId)
            ->where('pago', 1)
            ->where('tipo', LancamentoTipo::DESPESA->value)
            ->where('eh_transferencia', 0)
            ->where('afeta_caixa', 1)
            ->whereBetween('data', [$start, $end])
            ->selectRaw('SUM(' . $this->metaCoverageExpression('lancamentos') . ') as total')
            ->value('total');

        return (float) ($total ?? 0);
    }

    /**
     * Calcular saldo acumulado do usuário até uma data (inclusive).
     *
     * @param int $userId
     * @param string $ate Data limite (Y-m-d)
     * @return float
     */
    public function sumSaldoAcumuladoAte(int $userId, string $ate): float
    {
        $saldosIniciais = (float) Conta::forUser($userId)
            ->ativas()
            ->sum('saldo_inicial');

        $receitas = (float) $this->query()
            ->where('user_id', $userId)
            ->where('tipo', LancamentoTipo::RECEITA->value)
            ->where('eh_transferencia', 0)
            ->where('pago', 1)
            ->where('afeta_caixa', 1)
            ->where('data', '<=', $ate)
            ->sum('valor');

        $despesas = (float) $this->query()
            ->where('user_id', $userId)
            ->where('tipo', LancamentoTipo::DESPESA->value)
            ->where('eh_transferencia', 0)
            ->where('pago', 1)
            ->where('afeta_caixa', 1)
            ->where('data', '<=', $ate)
            ->sum('valor');

        return $saldosIniciais + $receitas - $despesas;
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
     * Obter resumo financeiro do mês para Health Score e Insights.
     *
     * @param int $userId
     * @param string $month Formato: Y-m
     * @return array{receitas?: float, despesas?: float, count?: int, categories?: int, saldo_atual?: float}
     */
    public function getResumoMes(int $userId, string $month): array
    {
        $period = $this->parseYearMonth($month);

        $receitas = (float) $this->buildResumoMesBaseQuery($userId, $period['year'], $period['monthNum'])
            ->where('tipo', LancamentoTipo::RECEITA->value)
            ->sum('valor');

        $despesas = (float) ($this->buildResumoMesBaseQuery($userId, $period['year'], $period['monthNum'])
            ->where('tipo', LancamentoTipo::DESPESA->value)
            ->selectRaw('SUM(' . $this->effectiveExpenseExpression('lancamentos') . ') as total')
            ->value('total') ?? 0);

        $count = $this->buildResumoMesBaseQuery($userId, $period['year'], $period['monthNum'])->count();

        $categories = (int) $this->buildResumoMesBaseQuery($userId, $period['year'], $period['monthNum'])
            ->whereNotNull('categoria_id')
            ->distinct()
            ->count('categoria_id');

        // Saldo atual = saldo inicial das contas + receitas pagas - despesas pagas (caixa)
        $saldosIniciais = (float) Conta::forUser($userId)
            ->ativas()
            ->sum('saldo_inicial');

        $receitasCaixa = (float) $this->query()
            ->where('user_id', $userId)
            ->where('tipo', LancamentoTipo::RECEITA->value)
            ->where('eh_transferencia', 0)
            ->where('pago', 1)
            ->where('afeta_caixa', 1)
            ->sum('valor');

        $despesasCaixa = (float) $this->query()
            ->where('user_id', $userId)
            ->where('tipo', LancamentoTipo::DESPESA->value)
            ->where('eh_transferencia', 0)
            ->where('pago', 1)
            ->where('afeta_caixa', 1)
            ->sum('valor');

        $saldoAtual = $saldosIniciais + $receitasCaixa - $despesasCaixa;

        return [
            'receitas'    => $receitas,
            'despesas'    => $despesas,
            'count'       => $count,
            'categories'  => $categories,
            'saldo_atual' => $saldoAtual,
        ];
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
        $period = $this->parseYearMonth($month);
        $start = $period['start'];
        $end = $period['end'];

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

    public function getRecentTransactions(int $userId, string $from, string $to, int $limit): Collection
    {
        $limit = max(0, $limit);
        if ($limit === 0) {
            return new Collection();
        }

        return Lancamento::query()
            ->withoutGlobalScopes()
            ->from('lancamentos as l')
            ->leftJoin('categorias as c', 'c.id', '=', 'l.categoria_id')
            ->leftJoin('contas as a', 'a.id', '=', 'l.conta_id')
            ->where('l.user_id', $userId)
            ->whereNull('l.deleted_at')
            ->whereBetween('l.data', [$from, $to])
            ->orderBy('l.data', 'desc')
            ->orderBy('l.id', 'desc')
            ->limit($limit)
            ->selectRaw('
            l.id, l.data, l.tipo, l.valor, l.descricao, l.pago,
            l.categoria_id, l.conta_id,
            COALESCE(c.nome, "") as categoria,
            COALESCE(c.icone, "") as categoria_icone,
            COALESCE(a.nome, a.instituicao, "") as conta
            ')
            ->get();
    }

    /**
     * Buscar transações para API financeira em um período.
     *
     * @param int $userId
     * @param string $from Data inicial (Y-m-d)
     * @param string $to Data final (Y-m-d)
     * @param int $limit Limite maximo de registros
     * @return Collection<int, Lancamento>
     */
    public function findTransactionsForPeriod(int $userId, string $from, string $to, int $limit): Collection
    {
        $limit = max(0, $limit);
        if ($limit === 0) {
            return new Collection();
        }

        return $this->query()
            ->with('categoria:id,nome')
            ->where('user_id', $userId)
            ->whereBetween('data', [$from, $to])
            ->where('eh_transferencia', 0)
            ->orderBy('data', 'desc')
            ->orderBy('id', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * @return array{year:int,monthNum:int,start:string,end:string}
     */
    private function parseYearMonth(string $month): array
    {
        $month = trim($month);
        $date = DateTimeImmutable::createFromFormat('!Y-m', $month);

        if (!$date || $date->format('Y-m') !== $month) {
            throw new InvalidArgumentException('Formato de mes invalido (YYYY-MM).');
        }

        return [
            'year' => (int) $date->format('Y'),
            'monthNum' => (int) $date->format('m'),
            'start' => $date->format('Y-m-01'),
            'end' => $date->format('Y-m-t'),
        ];
    }

    private function buildResumoMesBaseQuery(int $userId, int $year, int $monthNum): Builder
    {
        return $this->query()
            ->where('user_id', $userId)
            ->where('eh_transferencia', 0)
            ->where('eh_saldo_inicial', 0)
            ->whereYear('data', $year)
            ->whereMonth('data', $monthNum);
    }
    /**
     * Busca a soma de gastos de todas as categorias do usuário em um mês (Competência).
     * Resolve o problema de performancea N+1 no Dashboard.
     * * @return array<int, float> [categoria_id => valor_total]
     */
    public function getSomaGastosPorCategoria(int $userId, int $month, int $year): array
    {
        return $this->query()
            ->selectRaw('categoria_id, SUM(' . $this->effectiveExpenseExpression('lancamentos') . ') as total')
            ->where('user_id', $userId)
            ->where('tipo', LancamentoTipo::DESPESA->value)
            ->where('eh_transferencia', 0)
            ->where(function ($q) use ($month, $year) {
                // Lógica de competência com fallback para data
                $q->where(function ($sub) use ($month, $year) {
                    $sub->whereNotNull('data_competencia')
                        ->whereMonth('data_competencia', $month)
                        ->whereYear('data_competencia', $year);
                })->orWhere(function ($sub) use ($month, $year) {
                    $sub->whereNull('data_competencia')
                        ->whereMonth('data', $month)
                        ->whereYear('data', $year);
                });
            })
            ->groupBy('categoria_id')
            ->pluck('total', 'categoria_id')
            ->toArray();
    }

    /**
     * Retorna totais diários de receitas e despesas (caixa) para um mês.
     * Cada item: ['label' => 'DD', 'receitas' => float, 'despesas' => float]
     *
     * @return list<array{label:string,receitas:float,despesas:float}>
     */
    public function getDailyTotalsByMonth(int $userId, string $month): array
    {
        $period = $this->parseYearMonth($month);

        $rows = Lancamento::withoutGlobalScopes()
            ->selectRaw('DAY(data) as dia')
            ->selectRaw("SUM(CASE WHEN tipo = 'receita' THEN valor ELSE 0 END) as receitas")
            ->selectRaw("SUM(CASE WHEN tipo = 'despesa' THEN {$this->effectiveExpenseExpression('lancamentos')} ELSE 0 END) as despesas")
            ->where('user_id', $userId)
            ->where('pago', 1)
            ->where('eh_transferencia', 0)
            ->where('afeta_caixa', 1)
            ->whereIn('tipo', ['receita', 'despesa'])
            ->whereBetween('data', [$period['start'], $period['end']])
            ->groupByRaw('DAY(data)')
            ->orderByRaw('DAY(data)')
            ->get();

        $byDay = [];
        foreach ($rows as $row) {
            $d = (int) $row->dia;
            $byDay[$d] = [
                'receitas' => (float) ($row->receitas ?? 0),
                'despesas' => (float) ($row->despesas ?? 0),
            ];
        }

        $daysInMonth = (int) date('t', strtotime($period['start']));
        $result = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $result[] = [
                'label'    => str_pad((string) $d, 2, '0', STR_PAD_LEFT),
                'receitas' => $byDay[$d]['receitas'] ?? 0.0,
                'despesas' => $byDay[$d]['despesas'] ?? 0.0,
            ];
        }

        return $result;
    }

    /**
     * Busca totais de despesas por categoria em um período.
     * Aceita visualização por caixa ou competencia.
     *
     * @param int $userId
     * @param string $start Data inicial (Y-m-d)
     * @param string $end Data final (Y-m-d)
     * @param string $viewType caixa|competencia
     * @return Collection<int, Lancamento>
     */
    public function getDespesaTotalsByCategoria(int $userId, string $start, string $end, string $viewType = 'caixa'): Collection
    {
        $query = $this->query()
            ->where('user_id', $userId)
            ->where('tipo', LancamentoTipo::DESPESA->value)
            ->where('eh_transferencia', 0);

        if ($viewType === 'competencia') {
            $query->where(function ($q) use ($start, $end) {
                $q->where(function ($sub) use ($start, $end) {
                    $sub->whereNotNull('data_competencia')
                        ->whereBetween('data_competencia', [$start, $end]);
                })->orWhere(function ($sub) use ($start, $end) {
                    $sub->whereNull('data_competencia')
                        ->whereBetween('data', [$start, $end]);
                });
            });
        } else {
            $query->where('pago', 1)
                ->where('afeta_caixa', 1)
                ->whereBetween('data', [$start, $end]);
        }

        return $query
            ->selectRaw('categoria_id, SUM(' . $this->effectiveExpenseExpression('lancamentos') . ') as total')
            ->groupBy('categoria_id')
            ->get();
    }

    private function metaCoverageExpression(string $tableAlias = 'lancamentos'): string
    {
        $t = preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $tableAlias) ? $tableAlias : 'lancamentos';

        return "CASE
            WHEN {$t}.tipo <> 'despesa' THEN 0
            WHEN {$t}.meta_id IS NULL THEN 0
            WHEN (
                {$t}.meta_operacao IN ('resgate', 'realizacao')
                OR {$t}.meta_operacao IS NULL
                OR {$t}.meta_operacao = ''
            ) THEN LEAST({$t}.valor, GREATEST(COALESCE({$t}.meta_valor, {$t}.valor), 0))
            ELSE 0
        END";
    }

    private function effectiveExpenseExpression(string $tableAlias = 'lancamentos'): string
    {
        $t = preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $tableAlias) ? $tableAlias : 'lancamentos';
        $coverage = $this->metaCoverageExpression($t);

        return "GREATEST({$t}.valor - ({$coverage}), 0)";
    }
}
