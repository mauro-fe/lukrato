<?php

namespace Application\Controllers\Api\Financeiro;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Repositories\LancamentoRepository;
use Application\Repositories\MetaRepository;
use Application\Repositories\OrcamentoRepository;
use Application\Services\Financeiro\DashboardProvisaoService;
use Application\Services\Financeiro\HealthScoreService;
use Application\Services\Financeiro\DashboardInsightService;
use Application\Services\Financeiro\HealthScoreInsightService;
use Application\Services\Infrastructure\LogService;
use Application\Services\Financeiro\DashboardHealthSummaryService;
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

    public function __construct(
        ?LancamentoRepository $lancamentoRepo = null,
        ?DashboardProvisaoService $provisaoService = null,
        ?OrcamentoRepository $orcamentoRepo = null,
        ?MetaRepository $metaRepo = null,
        ?HealthScoreService $healthScoreService = null,
        ?DashboardInsightService $dashboardInsightService = null,
        ?HealthScoreInsightService $healthScoreInsightService = null,
        ?DashboardHealthSummaryService $dashboardHealthSummaryService = null
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
    }

    private function normalizeMonth(string $monthInput): array
    {
        return $this->normalizeYearMonth($monthInput);
    }

    private function getCurrentMonth(): string
    {
        return (new \DateTimeImmutable('first day of this month'))->format('Y-m');
    }

    private function getPreviousMonth(): string
    {
        return (new \DateTimeImmutable('first day of last month'))->format('Y-m');
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
            $from = $normalized['start'];
            $to = $normalized['end'];

            $out = $this->dashboardInsightService->getRecentTransactions($userId, $from, $to, $limit);

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
}
