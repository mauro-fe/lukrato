<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Dashboard\Concerns;

use Application\Models\Categoria;
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
        return $this->lancamentoRepo->sumSaldoAcumuladoAte($userId, $untilDate);
    }

    protected function getDespesasPorCategoria(int $userId, string $month, string $viewType): array
    {
        $normalized = $this->normalizeMonth($month);
        $start = $normalized['start'];
        $end = $normalized['end'];

        $rows = $this->lancamentoRepo->getDespesaTotalsByCategoria($userId, $start, $end, $viewType);

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

}
