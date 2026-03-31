<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Dashboard;

use Application\Controllers\Api\Dashboard\Concerns\HandlesDashboardRead;
use Application\Controllers\ApiController;
use Application\Core\Response;
use DateTimeImmutable;
use Throwable;

class OverviewController extends ApiController
{
    use HandlesDashboardRead;

    /**
     * GET /api/dashboard/overview
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

    /**
     * GET /api/dashboard/evolucao
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

            $mensal = $this->lancamentoRepo->getDailyTotalsByMonth($userId, $month);
            $annualMonths = $this->getPreviousMonths($month, 12);

            $anual = array_map(function (string $m) use ($userId, $viewType): array {
                $metrics = $this->buildMetricsPayload($userId, $m, $viewType);
                $date = DateTimeImmutable::createFromFormat('!Y-m', $m);
                $label = $date ? $date->format('M/y') : $m;

                return [
                    'label' => $label,
                    'month' => $m,
                    'receitas' => (float) ($metrics['receitas'] ?? 0),
                    'despesas' => (float) ($metrics['despesas'] ?? 0),
                    'saldo' => (float) ($metrics['resultado'] ?? 0),
                ];
            }, $annualMonths);

            return Response::successResponse([
                'month' => $month,
                'mensal' => $mensal,
                'anual' => $anual,
            ]);
        } catch (Throwable $e) {
            $this->logDashboardError('Erro ao gerar evolucao financeira', $e, $userId, [
                'month' => $month,
            ]);

            return $this->internalErrorResponse($e, 'Erro ao gerar evolucao.');
        }
    }
}
