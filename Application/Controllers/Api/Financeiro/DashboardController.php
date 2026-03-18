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
use Application\Services\Infrastructure\LogService;

class DashboardController extends BaseController
{
    private LancamentoRepository $lancamentoRepo;
    private DashboardProvisaoService $provisaoService;
    private OrcamentoRepository $orcamentoRepo;
    private MetaRepository $metaRepo;
    private HealthScoreService $healthScoreService;
    private DashboardInsightService $dashboardInsightService;

    public function __construct()
    {
        parent::__construct();
        $this->lancamentoRepo = new LancamentoRepository();
        $this->provisaoService = new DashboardProvisaoService();
        $this->orcamentoRepo = new OrcamentoRepository();
        $this->metaRepo = new MetaRepository();
        $this->healthScoreService = new HealthScoreService($this->lancamentoRepo, $this->orcamentoRepo, $this->metaRepo);
        $this->dashboardInsightService = new DashboardInsightService($this->lancamentoRepo, $this->metaRepo);
    }

    private function normalizeMonth(string $monthInput): array
    {
        return $this->normalizeYearMonth($monthInput);
    }

    public function comparativoCompetenciaCaixa(): void
    {
        $userId = $this->resolveCurrentUserIdOrFail('Nao autenticado');
        if ($userId === null) {
            return;
        }
        $this->releaseSession();

        $normalizedDate = $this->normalizeMonth((string) $this->getQuery('month', date('Y-m')));
        $month = $normalizedDate['month'];

        $comparativo = $this->lancamentoRepo->getResumoCompetenciaVsCaixa($userId, $month);

        $response = $this->dashboardInsightService->buildComparativoCompetenciaCaixaResponse($comparativo, $month);

        Response::success($response);
    }

    public function transactions(): void
    {
        $userId = $this->resolveCurrentUserIdOrFail('Nao autenticado');
        if ($userId === null) {
            return;
        }
        $this->releaseSession();

        $limit = min((int) $this->getQuery('limit', 5), 100);
        $normalized = $this->normalizeMonth((string) $this->getQuery('month', date('Y-m')));
        $from = $normalized['start'];
        $to = $normalized['end'];

        $out = $this->dashboardInsightService->getRecentTransactions($userId, $from, $to, $limit);

        Response::success($out);
    }

    public function provisao(): void
    {
        $userId = $this->resolveCurrentUserIdOrFail('Nao autenticado');
        if ($userId === null) {
            return;
        }
        $this->releaseSession();

        $normalized = $this->normalizeMonth((string) $this->getQuery('month', date('Y-m')));
        $result = $this->provisaoService->generate($userId, $normalized['month']);

        Response::success($result->toArray());
    }

    public function healthScore(): void
    {
        $userId = $this->resolveCurrentUserIdOrFail('Nao autenticado');
        if ($userId === null) {
            return;
        }
        $this->releaseSession();

        try {
            $currentMonth = date('Y-m');
            $score = $this->healthScoreService->calculateUserHealthScore($userId, $currentMonth);

            Response::success($score);
        } catch (\Exception $e) {
            LogService::error('Erro ao calcular health score no dashboard', [
                'user_id' => $userId,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            Response::error('Erro ao calcular health score', 500);
        }
    }

    public function greetingInsight(): void
    {
        $userId = $this->resolveCurrentUserIdOrFail('Nao autenticado');
        if ($userId === null) {
            return;
        }
        $this->releaseSession();

        try {
            $currentMonth = date('Y-m');
            $previousMonth = date('Y-m', strtotime('-1 month'));

            $insight = $this->dashboardInsightService->generateGreetingInsight($userId, $currentMonth, $previousMonth);

            Response::success($insight);
        } catch (\Exception $e) {
            Response::error('Erro ao gerar insight', 500);
        }
    }

    public function healthScoreInsights(): void
    {
        $userId = $this->resolveCurrentUserIdOrFail('Nao autenticado');
        if ($userId === null) {
            return;
        }
        $this->releaseSession();

        try {
            $currentMonth = date('Y-m');
            $insights = $this->healthScoreService->generateHealthScoreInsights($userId, $currentMonth);

            Response::success($insights);
        } catch (\Exception $e) {
            LogService::error('Erro ao gerar insights de health score', [
                'user_id' => $userId,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            Response::error('Erro ao gerar insights', 500);
        }
    }
}
