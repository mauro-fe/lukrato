<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Dashboard;

use Application\Controllers\Api\Dashboard\Concerns\HandlesDashboardRead;
use Application\Controllers\ApiController;
use Application\Core\Response;
use Throwable;

class HealthController extends ApiController
{
    use HandlesDashboardRead;

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
