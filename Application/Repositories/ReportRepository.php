<?php

namespace Application\Repositories;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Application\DTO\ReportParameters;
use Application\Enums\LancamentoTipo;

/**
 * Repositório para buscar dados brutos para os relatórios.
 * Todo o SQL complexo vive aqui.
 */
class ReportRepository
{
    // --- Métodos Públicos de Busca ---

    public function getCategoryTotals(string $tipo, ReportParameters $params): Collection
    {
        $query = $this->buildCategoryQuery($tipo, $params);

        if ($params->accountId === null) {
            return $this->getGlobalCategoryTotals($query);
        }

        return $this->getAccountCategoryTotals($query, $params->accountId, $tipo);
    }

    public function getDailyDelta(ReportParameters $params, bool $useTransfers): Collection
    {
        return $this->baseLancamentos($params->start, $params->end, $params, $useTransfers, true)
            ->select(DB::raw('DATE(lancamentos.data) as dia'))
            ->selectRaw(...$this->deltaExpression($params->accountId, 'delta'))
            ->groupBy(DB::raw('DATE(lancamentos.data)'))
            ->orderBy('dia')
            ->get()
            ->keyBy('dia');
    }

    public function getDailyRecDes(ReportParameters $params, bool $useTransfers): Collection
    {
        return $this->baseLancamentos($params->start, $params->end, $params, $useTransfers, true)
            ->selectRaw('DATE(lancamentos.data) as dia')
            ->selectRaw($this->sumByType('receitas', 'receita'))
            ->selectRaw($this->sumByType('despesas', 'despesa'))
            ->groupBy(DB::raw('DATE(lancamentos.data)'))
            ->orderBy('dia')
            ->get()
            ->keyBy('dia');
    }

    public function getMonthlyDelta(Carbon $start, Carbon $end, ReportParameters $params, bool $useTransfers): Collection
    {
        return $this->baseLancamentos($start, $end, $params, $useTransfers, true)
            ->selectRaw("DATE_FORMAT(lancamentos.data, '%Y-%m-01') as mes")
            ->selectRaw(...$this->deltaExpression($params->accountId, 'saldo'))
            ->groupBy('mes')
            ->orderBy('mes')
            ->get()
            ->keyBy('mes');
    }

    public function getTotalsByAccount(ReportParameters $params): Collection
    {
        return DB::table('contas')
            ->when($params->userId, fn($q) => $q->where('contas.user_id', $params->userId))
            ->when($params->accountId, fn($q) => $q->where('contas.id', $params->accountId))
            ->leftJoin('lancamentos as l', fn($join) => $this->joinAccountTransactions($join, $params))
            ->selectRaw($this->selectAccountName())
            ->selectRaw($this->sumAccountReceitas())
            ->selectRaw($this->sumAccountDespesas())
            ->groupBy('contas.id', 'conta')
            ->orderBy('conta')
            ->get();
    }

    public function getMonthlyRecDesForYear(Carbon $start, Carbon $end, ReportParameters $params, bool $useTransfers): Collection
    {
        return $this->baseLancamentos($start, $end, $params, $useTransfers, true)
            ->selectRaw('MONTH(lancamentos.data) as mes')
            ->selectRaw($this->sumByType('receitas', 'receita'))
            ->selectRaw($this->sumByType('despesas', 'despesa'))
            ->groupBy('mes')
            ->orderBy('mes')
            ->get();
    }

    public function saldoAte(Carbon $ate, ReportParameters $params, bool $useTransfers): float
    {
        // 1. Calcular delta dos lançamentos (respeitando afeta_caixa)
        $query = DB::table('lancamentos')
            ->where('lancamentos.data', '<=', $ate)
            ->where(function ($q) {
                $q->where('lancamentos.afeta_caixa', true)
                    ->orWhereNull('lancamentos.afeta_caixa'); // Backward compatibility
            })
            ->selectRaw(...$this->deltaExpression($params->accountId, 'saldo'));

        if (!$useTransfers) {
            $query->where('lancamentos.eh_transferencia', 0);
        }

        $this->applyUserScope($query, $params->userId);
        $this->applyAccountFilter($query, $params->accountId, $useTransfers);

        $deltaLancamentos = (float)($query->value('saldo') ?? 0.0);

        // 2. Adicionar saldo inicial das contas (apenas para visão global ou conta específica)
        $saldoInicial = $this->getSaldoInicialContas($params);

        return $saldoInicial + $deltaLancamentos;
    }

    /**
     * Obtém a soma dos saldos iniciais das contas do usuário.
     * Se accountId for especificado, retorna apenas o saldo inicial dessa conta.
     */
    private function getSaldoInicialContas(ReportParameters $params): float
    {
        $query = DB::table('contas')
            ->where('ativo', true);

        if ($params->userId) {
            $query->where('user_id', $params->userId);
        }

        if ($params->accountId) {
            $query->where('id', $params->accountId);
        }

        return (float)($query->sum('saldo_inicial') ?? 0.0);
    }

    // --- Builders de Query Específicos ---

    private function buildCategoryQuery(string $tipo, ReportParameters $params): QueryBuilder
    {
        $query = DB::table('lancamentos as l')
            ->leftJoin('categorias as c', 'c.id', '=', 'l.categoria_id')
            ->whereBetween('l.data', [$params->start, $params->end])
            ->where('l.tipo', $tipo);

        return $this->applyUserScope($query, $params->userId, 'l');
    }

    private function getGlobalCategoryTotals(QueryBuilder $query): Collection
    {
        return $query
            ->where('l.eh_transferencia', 0)
            ->selectRaw('COALESCE(c.id, 0) as cat_id')
            ->selectRaw("COALESCE(MAX(c.nome), 'Sem categoria') as label")
            ->selectRaw('SUM(l.valor) as total')
            ->groupBy('cat_id')
            ->orderByDesc('total')
            ->get();
    }

    private function getAccountCategoryTotals(QueryBuilder $query, int $accountId, string $tipo): Collection
    {
        return $query
            ->where(fn($w) => $this->applyAccountCategoryFilter($w, $accountId, $tipo))
            ->where(fn($w) => $this->applyAccountTypeFilter($w, $tipo))
            ->selectRaw($this->selectCategoryLabel())
            ->selectRaw('SUM(l.valor) as total')
            ->selectRaw('COALESCE(c.id, 0) as cat_id, l.eh_transferencia as is_transf')
            ->groupBy('is_transf', 'cat_id')
            ->orderByDesc('total')
            ->get();
    }

    private function applyAccountCategoryFilter(QueryBuilder $query, int $accountId, string $tipo): void
    {
        $query->where(function ($q1) use ($accountId) {
            $q1->where('l.eh_transferencia', 0)
                ->where('l.conta_id', $accountId);
        })
            ->orWhere(function ($q2) use ($accountId, $tipo) {
                $q2->where('l.eh_transferencia', 1);
                if ($tipo === LancamentoTipo::RECEITA->value) {
                    $q2->where('l.conta_id_destino', $accountId);
                } else {
                    $q2->where('l.conta_id', $accountId);
                }
            });
    }

    private function applyAccountTypeFilter(QueryBuilder $query, string $tipo): void
    {
        $query->where(function ($q) use ($tipo) {
            $q->where('l.eh_transferencia', 0)
                ->where('l.tipo', $tipo);
        })
            ->orWhere('l.eh_transferencia', 1);
    }

    private function joinAccountTransactions($join, ReportParameters $params): void
    {
        $join->on(DB::raw('1'), '=', DB::raw('1'))
            ->whereBetween('l.data', [$params->start, $params->end])
            ->where(fn($w) => $this->applyAccountTransactionFilter($w))
            ->where(fn($q) => $q->whereNull('l.user_id')->orWhere('l.user_id', $params->userId));
    }

    private function applyAccountTransactionFilter(QueryBuilder $query): void
    {
        $query->whereColumn('l.conta_id', 'contas.id')
            ->orWhere(function ($w) {
                $w->where('l.eh_transferencia', 1)
                    ->whereColumn('l.conta_id_destino', 'contas.id');
            });
    }

    // --- Query Base e Filtros Comuns ---

    private function baseLancamentos(
        Carbon $start,
        Carbon $end,
        ReportParameters $params,
        bool $useTransfers,
        bool $includeSaldoInicial = false,
        bool $respectAfetaCaixa = true
    ): QueryBuilder {
        $query = DB::table('lancamentos')
            ->whereBetween('lancamentos.data', [$start, $end]);

        if (!$includeSaldoInicial) {
            $query->where('lancamentos.eh_saldo_inicial', 0);
        }

        if (!$useTransfers) {
            $query->where('lancamentos.eh_transferencia', 0);
        }

        // Respeitar campo afeta_caixa para cálculos de saldo
        if ($respectAfetaCaixa) {
            $query->where(function ($q) {
                $q->where('lancamentos.afeta_caixa', true)
                    ->orWhereNull('lancamentos.afeta_caixa'); // Backward compatibility
            });
        }

        $this->applyUserScope($query, $params->userId);
        $this->applyAccountFilter($query, $params->accountId, $useTransfers);

        return $query;
    }

    private function applyUserScope(QueryBuilder $query, ?int $userId, string $tableAlias = 'lancamentos'): QueryBuilder
    {
        $column = $this->sanitizeColumn($tableAlias, 'user_id');

        return $query->where(function (QueryBuilder $q) use ($userId, $column) {
            $q->whereNull($column);
            if ($userId !== null) {
                $q->orWhere($column, $userId);
            }
        });
    }

    private function applyAccountFilter(QueryBuilder $query, ?int $accountId, bool $includeTransfers): QueryBuilder
    {
        if ($accountId === null) {
            return $query;
        }

        if ($includeTransfers) {
            return $query->where(function (QueryBuilder $w) use ($accountId) {
                $w->where('lancamentos.conta_id', $accountId)
                    ->orWhere(function (QueryBuilder $w2) use ($accountId) {
                        $w2->where('lancamentos.eh_transferencia', 1)
                            ->where('lancamentos.conta_id_destino', $accountId);
                    });
            });
        }

        return $query->where('lancamentos.conta_id', $accountId);
    }

    // --- Expressões SQL Reutilizáveis ---

    private function deltaExpression(?int $accountId, string $alias = 'delta'): array
    {
        $alias = $this->sanitizeAlias($alias);

        if ($accountId) {
            return [
                "SUM(
                    CASE
                        WHEN lancamentos.eh_transferencia = 1 THEN
                            CASE
                                WHEN lancamentos.conta_id = ? THEN -lancamentos.valor
                                WHEN lancamentos.conta_id_destino = ? THEN lancamentos.valor
                                ELSE 0
                            END
                        ELSE
                            CASE
                                WHEN lancamentos.tipo = 'receita' THEN lancamentos.valor
                                WHEN lancamentos.tipo = 'despesa' THEN -lancamentos.valor
                                ELSE 0
                            END
                    END
                ) as {$alias}",
                [$accountId, $accountId]
            ];
        }

        return [
            "SUM(
                CASE
                    WHEN lancamentos.eh_transferencia = 1 THEN 0
                    WHEN lancamentos.tipo = 'receita' THEN lancamentos.valor
                    WHEN lancamentos.tipo = 'despesa' THEN -lancamentos.valor
                    ELSE 0
                END
            ) as {$alias}",
            []
        ];
    }

    private function sumByType(string $alias, string $tipo): string
    {
        return "SUM(CASE WHEN lancamentos.tipo='{$tipo}' THEN lancamentos.valor ELSE 0 END) as {$alias}";
    }

    private function selectAccountName(): string
    {
        return "COALESCE(contas.nome, contas.instituicao, 'Sem conta') as conta";
    }

    private function selectCategoryLabel(): string
    {
        return 'CASE
            WHEN l.eh_transferencia = 1 THEN "Transferência"
            ELSE COALESCE(MAX(c.nome), "Sem categoria")
        END as label';
    }

    private function sumAccountReceitas(): string
    {
        return "SUM(
            CASE
                WHEN l.eh_transferencia = 0 AND l.tipo = 'receita' AND l.conta_id = contas.id THEN l.valor
                WHEN l.eh_transferencia = 1 AND l.conta_id_destino = contas.id THEN l.valor
                ELSE 0
            END
        ) as receitas";
    }

    private function sumAccountDespesas(): string
    {
        return "SUM(
            CASE
                WHEN l.eh_transferencia = 0 AND l.tipo = 'despesa' AND l.conta_id = contas.id THEN l.valor
                WHEN l.eh_transferencia = 1 AND l.conta_id = contas.id THEN l.valor
                ELSE 0
            END
        ) as despesas";
    }

    // --- Sanitização ---

    private function sanitizeAlias(string $alias): string
    {
        return preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $alias) ? $alias : 'delta';
    }

    private function sanitizeColumn(string $table, string $column): string
    {
        $table = preg_match('/^[a-zA-Z0-9_]+$/', $table) ? $table : 'lancamentos';
        return "{$table}.{$column}";
    }
}
