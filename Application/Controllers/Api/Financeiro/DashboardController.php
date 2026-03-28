<?php

namespace Application\Controllers\Api\Financeiro;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Models\Conta;
use Application\Models\Lancamento;
use Application\Models\Categoria;
use Application\Repositories\LancamentoRepository;
use Application\Repositories\MetaRepository;
use Application\Repositories\OrcamentoRepository;
use Application\Services\Conta\ContaService;
use Application\Services\Demo\DemoPreviewService;
use Application\Services\Financeiro\DashboardProvisaoService;
use Application\Services\Financeiro\HealthScoreService;
use Application\Services\Financeiro\DashboardInsightService;
use Application\Services\Financeiro\HealthScoreInsightService;
use Application\Services\Infrastructure\LogService;
use Application\Services\Financeiro\DashboardHealthSummaryService;
use Application\Services\Plan\PlanLimitService;
use DateTimeImmutable;
use Throwable;

class DashboardController extends BaseController
{
    private LancamentoRepository $lancamentoRepo;
    private DashboardProvisaoService $provisaoService;
    private OrcamentoRepository $orcamentoRepo;
    private MetaRepository $metaRepo;
    private HealthScoreService $healthScoreService;
    private DashboardInsightService $dashboardInsightService;
    private HealthScoreInsightService $healthScoreInsightService;
    private DashboardHealthSummaryService $dashboardHealthSummaryService;
    private PlanLimitService $planLimitService;
    private ContaService $contaService;
    private DemoPreviewService $demoPreviewService;

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

        // Injeções que dependem de outros repositórios
        $this->healthScoreService = $healthScoreService ?? new HealthScoreService($this->lancamentoRepo, $this->orcamentoRepo, $this->metaRepo);
        $this->dashboardInsightService = $dashboardInsightService ?? new DashboardInsightService($this->lancamentoRepo);
        $this->healthScoreInsightService = $healthScoreInsightService ?? new HealthScoreInsightService($this->lancamentoRepo, $this->metaRepo);
        $this->dashboardHealthSummaryService = $dashboardHealthSummaryService ??
            new DashboardHealthSummaryService(
                $this->healthScoreService,
                $this->healthScoreInsightService
            );
        $this->planLimitService = $planLimitService ?? new PlanLimitService();
        $this->contaService = $contaService ?? new ContaService();
        $this->demoPreviewService = $demoPreviewService ?? new DemoPreviewService();
    }

    private function normalizeMonth(string $monthInput): array
    {
        return $this->normalizeYearMonth($monthInput);
    }

    private function getCurrentMonth(): string
    {
        return (new DateTimeImmutable('first day of this month'))->format('Y-m');
    }

    private function getPreviousMonth(): string
    {
        return (new DateTimeImmutable('first day of last month'))->format('Y-m');
    }

    private function getPreviousMonthFrom(string $month): string
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
    private function getPreviousMonths(string $currentMonth, int $count): array
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m', $currentMonth) ?: new DateTimeImmutable('first day of this month');
        $months = [];

        for ($i = $count - 1; $i >= 0; $i--) {
            $months[] = $date->modify(sprintf('-%d month', $i))->format('Y-m');
        }

        return $months;
    }

    private function buildMetricsPayload(int $userId, string $month, string $viewType = 'caixa'): array
    {
        $period = $this->normalizeMonth($month);
        $startStr = $period['start'];
        $endStr = $period['end'];
        $summary = $this->lancamentoRepo->getResumoMes($userId, $month);

        if ($viewType === 'competencia') {
            $receitas = $this->lancamentoRepo->sumReceitasCompetencia($userId, $startStr, $endStr);
            $despesas = $this->lancamentoRepo->sumDespesasCompetencia($userId, $startStr, $endStr);
        } else {
            $receitas = $this->lancamentoRepo->sumReceitasCaixa($userId, $startStr, $endStr);
            $despesas = $this->lancamentoRepo->sumDespesasCaixa($userId, $startStr, $endStr);
        }

        $resultado = $receitas - $despesas;
        $saldoAcumulado = $this->calculateSaldoAcumulado($userId, $endStr);

        return [
            'saldo' => $saldoAcumulado,
            'receitas' => $receitas,
            'despesas' => $despesas,
            'resultado' => $resultado,
            'saldoAcumulado' => $saldoAcumulado,
            'view' => $viewType,
            'count' => (int) ($summary['count'] ?? 0),
            'categories' => (int) ($summary['categories'] ?? 0),
        ];
    }

    private function calculateSaldoAcumulado(int $userId, string $untilDate): float
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

    private function getDespesasPorCategoria(int $userId, string $month, string $viewType): array
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
            ->selectRaw('categoria_id, SUM(valor) as total')
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

    /**
     * GET /api/dashboard/overview
     * Endpoint agregado para reduzir fanout de requests no dashboard.
     */
    public function overview(): Response
    {
        $userId = $this->requireApiUserIdAndReleaseSessionOrFail();
        $month = null;
        $limit = max(1, min($this->getIntQuery('limit', 5), 20));
        $viewType = $this->getStringQuery('view', 'caixa');

        try {
            $normalized = $this->normalizeMonth($this->getStringQuery('month', $this->getCurrentMonth()));
            $month = $normalized['month'];
            $previousMonth = $this->getPreviousMonthFrom($month);

            if ($this->demoPreviewService->shouldUsePreview($userId)) {
                return Response::successResponse(
                    $this->demoPreviewService->dashboardOverview(
                        $month,
                        $limit,
                        $this->planLimitService->getLimitsSummary($userId)
                    )
                );
            }

            $metrics = $this->buildMetricsPayload($userId, $month, $viewType);
            $chartMonths = $this->getPreviousMonths($month, 6);

            $chart = array_map(function (string $chartMonth) use ($userId, $viewType): array {
                $chartMetrics = $this->buildMetricsPayload($userId, $chartMonth, $viewType);

                return [
                    'month' => $chartMonth,
                    'resultado' => (float) ($chartMetrics['resultado'] ?? 0),
                ];
            }, $chartMonths);

            $accounts = $this->contaService->listarContas(
                userId: $userId,
                arquivadas: false,
                apenasAtivas: true,
                comSaldos: true,
                mes: $month
            );
            $accountCount = is_countable($accounts) ? count($accounts) : 0;

            $overview = [
                'month' => $month,
                'metrics' => $metrics,
                'accounts_balances' => $accounts,
                'recent_transactions' => $this->dashboardInsightService->getRecentTransactions(
                    $userId,
                    $normalized['start'],
                    $normalized['end'],
                    $limit
                ),
                'chart' => $chart,
                'despesas_por_categoria' => $this->getDespesasPorCategoria($userId, $month, $viewType),
                'provisao' => $this->provisaoService->generate($userId, $month)->toArray(),
                'health_score' => $this->healthScoreService->calculateUserHealthScore($userId, $month),
                'health_score_insights' => $this->healthScoreInsightService->generate($userId, $month),
                'greeting_insight' => $this->dashboardInsightService->generateGreetingInsight($userId, $month, $previousMonth),
                'plan' => $this->planLimitService->getLimitsSummary($userId),
                'meta' => [
                    'is_demo' => false,
                    'source' => 'real_data',
                    'context' => 'dashboard',
                    'primary_action' => $accountCount > 0 ? 'create_transaction' : 'create_account',
                    'cta_label' => $accountCount > 0 ? 'Adicionar agora' : 'Criar primeira conta',
                    'cta_url' => $accountCount > 0 ? 'lancamentos' : 'contas',
                    'real_account_count' => $accountCount,
                    'real_transaction_count' => (int) ($metrics['count'] ?? 0),
                    'real_category_count' => (int) ($metrics['categories'] ?? 0),
                ],
            ];

            return Response::successResponse($overview);
        } catch (Throwable $e) {
            $this->logDashboardError('Erro ao gerar overview do dashboard', $e, $userId, [
                'month' => $month,
                'limit' => $limit,
                'view' => $viewType,
            ]);

            return $this->internalErrorResponse($e, 'Erro ao carregar overview do dashboard.');
        }
    }

    public function comparativoCompetenciaCaixa(): Response
    {
        $userId = $this->requireApiUserIdAndReleaseSessionOrFail();

        $month = null;

        try {
            $normalizedDate = $this->normalizeMonth($this->getStringQuery('month', $this->getCurrentMonth()));
            $month = $normalizedDate['month'];

            $comparativo = $this->lancamentoRepo->getResumoCompetenciaVsCaixa($userId, $month);
            $response = $this->dashboardInsightService->buildComparativoCompetenciaCaixaResponse($comparativo, $month);

            return Response::successResponse($response);
        } catch (Throwable $e) {
            $this->logDashboardError('Erro ao gerar comparativo de competencia x caixa', $e, $userId, [
                'month' => $month,
            ]);

            return $this->internalErrorResponse($e, 'Erro ao gerar comparativo.');
        }
    }

    public function transactions(): Response
    {
        $userId = $this->requireApiUserIdAndReleaseSessionOrFail();

        $month = null;
        $limit = max(0, min($this->getIntQuery('limit', 5), 100));

        try {
            $normalized = $this->normalizeMonth($this->getStringQuery('month', $this->getCurrentMonth()));
            $month = $normalized['month'];
            $out = $this->dashboardInsightService->getRecentTransactions($userId, $normalized['start'], $normalized['end'], $limit);

            return Response::successResponse($out);
        } catch (Throwable $e) {
            $this->logDashboardError('Erro ao listar transacoes recentes do dashboard', $e, $userId, [
                'month' => $month,
                'limit' => $limit,
            ]);

            return $this->internalErrorResponse($e, 'Erro ao listar transacoes.');
        }
    }

    public function provisao(): Response
    {
        $userId = $this->requireApiUserIdAndReleaseSessionOrFail();

        try {
            $normalized = $this->normalizeMonth($this->getStringQuery('month', $this->getCurrentMonth()));
            $result = $this->provisaoService->generate($userId, $normalized['month']);

            return Response::successResponse($result->toArray());
        } catch (Throwable $e) {
            $this->logDashboardError('Erro ao gerar provisao do dashboard', $e, $userId);

            return $this->internalErrorResponse($e, 'Erro ao gerar provisao.');
        }
    }

    public function healthScore(): Response
    {
        $userId = $this->requireApiUserIdAndReleaseSessionOrFail();
        $currentMonth = null;

        try {
            $currentMonth = $this->getCurrentMonth();
            $score = $this->healthScoreService->calculateUserHealthScore($userId, $currentMonth);

            return Response::successResponse($score);
        } catch (Throwable $e) {
            $this->logDashboardError('Erro ao calcular health score no dashboard', $e, $userId, [
                'month' => $currentMonth,
            ]);

            return $this->internalErrorResponse($e, 'Erro ao calcular health score.');
        }
    }

    public function greetingInsight(): Response
    {
        $userId = $this->requireApiUserIdAndReleaseSessionOrFail();
        $currentMonth = null;
        $previousMonth = null;

        try {
            $currentMonth = $this->getCurrentMonth();
            $previousMonth = $this->getPreviousMonth();

            $insight = $this->dashboardInsightService->generateGreetingInsight($userId, $currentMonth, $previousMonth);

            return Response::successResponse($insight);
        } catch (Throwable $e) {
            $this->logDashboardError('Erro ao gerar insight do dashboard', $e, $userId, [
                'current_month' => $currentMonth,
                'previous_month' => $previousMonth,
            ]);

            return $this->internalErrorResponse($e, 'Erro ao gerar insight.');
        }
    }

    public function healthScoreInsights(): Response
    {
        $userId = $this->requireApiUserIdAndReleaseSessionOrFail();
        $currentMonth = null;

        try {
            $currentMonth = $this->getCurrentMonth();
            $insights = $this->healthScoreInsightService->generate($userId, $currentMonth);

            return Response::successResponse($insights);
        } catch (Throwable $e) {
            $this->logDashboardError('Erro ao gerar insights de health score', $e, $userId, [
                'month' => $currentMonth,
            ]);

            return $this->internalErrorResponse($e, 'Erro ao gerar insights.');
        }
    }

    private function logDashboardError(string $message, Throwable $e, int $userId, array $context = []): void
    {
        LogService::error($message, array_merge($context, [
            'user_id' => $userId,
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]));
    }

    public function healthSummary(): Response
    {
        $userId = $this->requireApiUserIdAndReleaseSessionOrFail();
        $currentMonth = null;

        try {
            $currentMonth = $this->getCurrentMonth();

            $summary = $this->dashboardHealthSummaryService->generate($userId, $currentMonth);

            return Response::successResponse($summary);
        } catch (Throwable $e) {
            $this->logDashboardError('Erro ao gerar resumo do dashboard', $e, $userId, [
                'month' => $currentMonth,
            ]);

            return $this->internalErrorResponse($e, 'Erro ao gerar resumo.');
        }
    }

    /**
     * GET /api/dashboard/evolucao
     * Retorna dados de evolução financeira: mensal (diário) e anual (12 meses).
     */
    public function evolucao(): Response
    {
        $userId = $this->requireApiUserIdAndReleaseSessionOrFail();
        $month = null;

        try {
            $normalized = $this->normalizeMonth($this->getStringQuery('month', $this->getCurrentMonth()));
            $month = $normalized['month'];
            $viewType = $this->getStringQuery('view', 'caixa');

            if ($this->demoPreviewService->shouldUsePreview($userId)) {
                return Response::successResponse($this->demoPreviewService->dashboardEvolucao($month));
            }

            // ── Mensal: totais por dia no mês selecionado ─────────────────────
            $mensal = $this->lancamentoRepo->getDailyTotalsByMonth($userId, $month);

            // ── Anual: últimos 12 meses ────────────────────────────────────────
            $annualMonths = $this->getPreviousMonths($month, 12);
            $anual = array_map(function (string $m) use ($userId, $viewType): array {
                $metrics = $this->buildMetricsPayload($userId, $m, $viewType);
                $date = DateTimeImmutable::createFromFormat('!Y-m', $m);
                $label = $date ? $date->format('M/y') : $m;
                return [
                    'label'    => $label,
                    'month'    => $m,
                    'receitas' => (float) ($metrics['receitas'] ?? 0),
                    'despesas' => (float) ($metrics['despesas'] ?? 0),
                    'saldo'    => (float) ($metrics['resultado'] ?? 0),
                ];
            }, $annualMonths);

            return Response::successResponse([
                'month'  => $month,
                'mensal' => $mensal,
                'anual'  => $anual,
            ]);
        } catch (Throwable $e) {
            $this->logDashboardError('Erro ao gerar evolução financeira', $e, $userId ?? 0, [
                'month' => $month,
            ]);

            return $this->internalErrorResponse($e, 'Erro ao gerar evolução.');
        }
    }
}
