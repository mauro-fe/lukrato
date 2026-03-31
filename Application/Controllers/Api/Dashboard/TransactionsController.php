<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Dashboard;

use Application\Controllers\Api\Dashboard\Concerns\HandlesDashboardRead;
use Application\Controllers\ApiController;
use Application\Core\Response;
use Throwable;

class TransactionsController extends ApiController
{
    use HandlesDashboardRead;

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
}
