<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Dashboard\Concerns;

use Application\Models\Categoria;
use Application\Models\Conta;
use Application\Models\Lancamento;
use Application\Repositories\LancamentoRepository;
use Application\Repositories\MetaRepository;
use Application\Repositories\OrcamentoRepository;
use Application\Services\Conta\ContaService;
use Application\Services\Demo\DemoPreviewService;
use Application\Services\Financeiro\DashboardHealthSummaryService;
use Application\Services\Financeiro\DashboardInsightService;
use Application\Services\Financeiro\DashboardProvisaoService;
use Application\Services\Financeiro\HealthScoreInsightService;
use Application\Services\Financeiro\HealthScoreService;
use Application\Services\Infrastructure\LogService;
use Application\Services\Plan\PlanLimitService;
use DateTimeImmutable;
use Throwable;

trait HandlesDashboardRead
{
    protected LancamentoRepository $lancamentoRepo;
    protected DashboardProvisaoService $provisaoService;
    protected OrcamentoRepository $orcamentoRepo;
    protected MetaRepository $metaRepo;
    protected HealthScoreService $healthScoreService;
    protected DashboardInsightService $dashboardInsightService;
    protected HealthScoreInsightService $healthScoreInsightService;
    protected DashboardHealthSummaryService $dashboardHealthSummaryService;
    protected PlanLimitService $planLimitService;
    protected ContaService $contaService;
    protected DemoPreviewService $demoPreviewService;

    public function __construct(
        ?LancamentoRepository $lancamentoRepo = null,
        ?DashboardProvisaoService $provisaoService = null,
        ?OrcamentoRepository $orcamentoRepo = null,
        ?MetaRepository $metaRepo = null,
        ?HealthScoreService $healthScoreService = null,
        ?DashboardInsightService $dashboardInsightService = null,
        ?HealthScoreInsightService $healthScoreInsightService = null,
        ?DashboardHealthSummaryService $dashboardHealthSummaryService = null,
        ?PlanLimitService $planLimitService = null,
        ?ContaService $contaService = null,
        ?DemoPreviewService $demoPreviewService = null
    ) {
        parent::__construct();

        $this->lancamentoRepo = $lancamentoRepo ?? new LancamentoRepository();
        $this->provisaoService = $provisaoService ?? new DashboardProvisaoService();
        $this->orcamentoRepo = $orcamentoRepo ?? new OrcamentoRepository();
        $this->metaRepo = $metaRepo ?? new MetaRepository();
        $this->healthScoreService = $healthScoreService
            ?? new HealthScoreService($this->lancamentoRepo, $this->orcamentoRepo, $this->metaRepo);
        $this->dashboardInsightService = $dashboardInsightService ?? new DashboardInsightService($this->lancamentoRepo);
        $this->healthScoreInsightService = $healthScoreInsightService
            ?? new HealthScoreInsightService($this->lancamentoRepo, $this->metaRepo);
        $this->dashboardHealthSummaryService = $dashboardHealthSummaryService
            ?? new DashboardHealthSummaryService($this->healthScoreService, $this->healthScoreInsightService);
        $this->planLimitService = $planLimitService ?? new PlanLimitService();
        $this->contaService = $contaService ?? new ContaService();
        $this->demoPreviewService = $demoPreviewService ?? new DemoPreviewService();
    }

    /**
     * @return array{month:string,year:int,monthNum:int,start:string,end:string}
     */
    protected function normalizeMonth(string $monthInput): array
    {
        return $this->normalizeYearMonth($monthInput);
    }

    protected function getCurrentMonth(): string
    {
        return (new DateTimeImmutable('first day of this month'))->format('Y-m');
    }

    protected function getPreviousMonth(): string
    {
        return (new DateTimeImmutable('first day of last month'))->format('Y-m');
    }

    protected function getPreviousMonthFrom(string $month): string
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m', $month);
        if (!$date) {
            return $this->getPreviousMonth();
        }

        return $date->modify('first day of last month')->format('Y-m');
    }

    /**
     * @return list<string>
     */
    protected function getPreviousMonths(string $currentMonth, int $count): array
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m', $currentMonth) ?: new DateTimeImmutable('first day of this month');
        $months = [];

        for ($i = $count - 1; $i >= 0; $i--) {
            $months[] = $date->modify(sprintf('-%d month', $i))->format('Y-m');
        }

        return $months;
    }

    protected function buildMetricsPayload(int $userId, string $month, string $viewType = 'caixa'): array
    {
        $period = $this->normalizeMonth($month);
        $startStr = $period['start'];
        $endStr = $period['end'];
        $summary = $this->lancamentoRepo->getResumoMes($userId, $month);

        if ($viewType === 'competencia') {
            $receitas = $this->lancamentoRepo->sumReceitasCompetencia($userId, $startStr, $endStr);
            $despesas = $this->lancamentoRepo->sumDespesasCompetencia($userId, $startStr, $endStr);
            $despesasBrutas = $this->lancamentoRepo->sumDespesasBrutasCompetencia($userId, $startStr, $endStr);
            $usoMetas = $this->lancamentoRepo->sumUsoMetasDespesaCompetencia($userId, $startStr, $endStr);
        } else {
            $receitas = $this->lancamentoRepo->sumReceitasCaixa($userId, $startStr, $endStr);
            $despesas = $this->lancamentoRepo->sumDespesasCaixa($userId, $startStr, $endStr);
            $despesasBrutas = $this->lancamentoRepo->sumDespesasBrutasCaixa($userId, $startStr, $endStr);
            $usoMetas = $this->lancamentoRepo->sumUsoMetasDespesaCaixa($userId, $startStr, $endStr);
        }

        $resultado = $receitas - $despesas;
        $saldoAcumulado = $this->calculateSaldoAcumulado($userId, $endStr);

        return [
            'saldo' => $saldoAcumulado,
            'receitas' => $receitas,
            'despesas' => $despesas,
            'despesas_brutas' => $despesasBrutas,
            'uso_metas' => $usoMetas,
            'resultado' => $resultado,
            'saldoAcumulado' => $saldoAcumulado,
            'view' => $viewType,
            'count' => (int) ($summary['count'] ?? 0),
            'categories' => (int) ($summary['categories'] ?? 0),
        ];
    }

    protected function calculateSaldoAcumulado(int $userId, string $untilDate): float
    {
        $saldosIniciais = (float) Conta::forUser($userId)
            ->ativas()
            ->sum('saldo_inicial');

        $receitas = (float) Lancamento::where('user_id', $userId)
            ->where('tipo', 'receita')
            ->where('eh_transferencia', 0)
            ->where('pago', 1)
            ->where('afeta_caixa', 1)
            ->where('data', '<=', $untilDate)
            ->sum('valor');

        $despesas = (float) Lancamento::where('user_id', $userId)
            ->where('tipo', 'despesa')
            ->where('eh_transferencia', 0)
            ->where('pago', 1)
            ->where('afeta_caixa', 1)
            ->where('data', '<=', $untilDate)
            ->sum('valor');

        return $saldosIniciais + $receitas - $despesas;
    }

    protected function getDespesasPorCategoria(int $userId, string $month, string $viewType): array
    {
        $normalized = $this->normalizeMonth($month);
        $start = $normalized['start'];
        $end = $normalized['end'];

        $query = Lancamento::where('user_id', $userId)
            ->where('tipo', 'despesa')
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

        $rows = $query
            ->selectRaw('categoria_id, SUM(' . $this->effectiveExpenseExpression('lancamentos') . ') as total')
            ->groupBy('categoria_id')
            ->get();

        $categoriaIds = $rows->pluck('categoria_id')->filter()->all();
        $categorias = Categoria::whereIn('id', $categoriaIds)
            ->get(['id', 'nome', 'icone'])
            ->keyBy('id');

        return $rows->map(function ($row) use ($categorias) {
            $catId = $row->categoria_id;
            $cat = $categorias[$catId] ?? null;

            return [
                'categoria' => $cat->nome ?? 'Sem categoria',
                'icone' => $cat->icone ?? null,
                'valor' => (float) $row->total,
            ];
        })->sortByDesc('valor')->values()->toArray();
    }

    protected function logDashboardError(string $message, Throwable $e, int $userId, array $context = []): void
    {
        LogService::error($message, array_merge($context, [
            'user_id' => $userId,
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]));
    }

    protected function metaCoverageExpression(string $tableAlias = 'lancamentos'): string
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

    protected function effectiveExpenseExpression(string $tableAlias = 'lancamentos'): string
    {
        $t = preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $tableAlias) ? $tableAlias : 'lancamentos';
        $coverage = $this->metaCoverageExpression($t);

        return "GREATEST({$t}.valor - ({$coverage}), 0)";
    }
}
