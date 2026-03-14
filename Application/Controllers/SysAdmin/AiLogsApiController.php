<?php

declare(strict_types=1);

namespace Application\Controllers\SysAdmin;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Lib\Auth;
use Application\Services\AI\AiLogService;

class AiLogsApiController extends BaseController
{
    private function isAdmin(): bool
    {
        $user = Auth::user();
        return $user && $user->is_admin == 1;
    }

    /**
     * GET /api/sysadmin/ai/logs
     */
    public function index(): void
    {
        $this->requireAuthApi();

        if (!$this->isAdmin()) {
            Response::error('Acesso negado', 403);
            return;
        }

        $filters = [
            'type'      => $_GET['type'] ?? null,
            'channel'   => $_GET['channel'] ?? null,
            'success'   => $_GET['success'] ?? '',
            'search'    => $_GET['search'] ?? null,
            'date_from' => $_GET['date_from'] ?? null,
            'date_to'   => $_GET['date_to'] ?? null,
            'page'      => $_GET['page'] ?? 1,
            'per_page'  => $_GET['per_page'] ?? 20,
        ];

        Response::success(AiLogService::query($filters));
    }

    /**
     * GET /api/sysadmin/ai/logs/summary
     */
    public function summary(): void
    {
        $this->requireAuthApi();

        if (!$this->isAdmin()) {
            Response::error('Acesso negado', 403);
            return;
        }

        $hours = max(1, (int) ($_GET['hours'] ?? 24));

        Response::success(AiLogService::summary($hours));
    }

    /**
     * DELETE /api/sysadmin/ai/logs/cleanup
     */
    public function cleanup(): void
    {
        $this->requireAuthApi();

        if (!$this->isAdmin()) {
            Response::error('Acesso negado', 403);
            return;
        }

        $payload = $this->getRequestPayload();
        $days = max(1, (int) ($payload['days'] ?? 90));

        $deleted = AiLogService::cleanup($days);

        Response::success([
            'deleted' => $deleted,
            'message' => "Removidos {$deleted} registros com mais de {$days} dias.",
        ]);
    }

    /**
     * GET /api/sysadmin/ai/logs/quality
     *
     * Métricas de qualidade semântica da IA:
     * - low_confidence_rate: % de respostas com confidence < 0.6
     * - fallback_to_chat_rate: % de chamadas caindo no ChatHandler
     * - intent_distribution: distribuição por tipo de intent
     * - error_by_handler: erros agrupados por handler
     * - source_distribution: rule vs llm vs cache vs computed
     * - avg_response_time_by_type: latência por handler
     */
    public function quality(): void
    {
        $this->requireAuthApi();

        if (!$this->isAdmin()) {
            Response::error('Acesso negado', 403);
            return;
        }

        $hours = max(1, (int) ($_GET['hours'] ?? 24));

        Response::success(AiLogService::qualityMetrics($hours));
    }
}
