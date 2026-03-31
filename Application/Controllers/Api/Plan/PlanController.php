<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Plan;

use Application\Controllers\ApiController;
use Application\Core\Response;
use Application\Enums\LogCategory;
use Application\Services\Infrastructure\LogService;
use Application\Services\Plan\PlanLimitService;

/**
 * Controller para consultar limites e status do plano do usuario
 */
class PlanController extends ApiController
{
    private PlanLimitService $limitService;

    public function __construct(?PlanLimitService $limitService = null)
    {
        parent::__construct();
        $this->limitService = $limitService ?? new PlanLimitService();
    }

    /**
     * GET /api/plan/limits
     * Retorna todos os limites e uso atual do usuario
     */
    public function limits(): Response
    {
        $userId = $this->requireApiUserIdAndReleaseSessionOrFail();

        try {
            $summary = $this->limitService->getLimitsSummary($userId);

            return Response::successResponse($summary);
        } catch (\Throwable $e) {
            LogService::captureException($e, LogCategory::SUBSCRIPTION, [
                'action' => 'get_plan_limits',
                'user_id' => $userId,
            ], $userId);

            $freeConfig = $this->limitService->getConfig()['limits']['free'] ?? [];

            return Response::successResponse([
                'plan' => 'free',
                'is_pro' => false,
                'contas' => ['allowed' => true, 'limit' => $freeConfig['max_contas'] ?? 2, 'used' => 0],
                'cartoes' => ['allowed' => true, 'limit' => $freeConfig['max_cartoes'] ?? 1, 'used' => 0],
                'categorias' => ['allowed' => true, 'limit' => $freeConfig['max_categorias_custom'] ?? 10, 'used' => 0],
                'metas' => ['allowed' => true, 'limit' => $freeConfig['max_metas'] ?? 2, 'used' => 0],
                'historico' => ['restricted' => true, 'months_limit' => $freeConfig['historico_meses'] ?? 3],
                'features' => [],
                'upgrade_url' => '/assinatura',
            ]);
        }
    }

    /**
     * GET /api/plan/features
     * Retorna as features disponiveis para o usuario
     */
    public function features(): Response
    {
        $userId = $this->requireApiUserIdAndReleaseSessionOrFail();
        $isPro = $this->limitService->isPro($userId);
        $features = $this->limitService->getFeatures($userId);

        return Response::successResponse([
            'plan' => $isPro ? 'pro' : 'free',
            'is_pro' => $isPro,
            'features' => $features,
        ]);
    }

    /**
     * GET /api/plan/can-create/{resource}
     * Verifica se o usuario pode criar um recurso especifico
     */
    public function canCreate(string $resource): Response
    {
        $userId = $this->requireApiUserIdAndReleaseSessionOrFail();

        $result = match ($resource) {
            'conta', 'contas', 'account', 'accounts' => $this->limitService->canCreateConta($userId),
            'cartao', 'cartoes', 'card', 'cards' => $this->limitService->canCreateCartao($userId),
            'categoria', 'categorias', 'category', 'categories' => $this->limitService->canCreateCategoria($userId),
            'meta', 'metas', 'goal', 'goals' => $this->limitService->canCreateMeta($userId),
            default => [
                'allowed' => true,
                'error' => 'Recurso nao rastreado',
            ],
        };

        return Response::successResponse(['resource' => $resource, ...$result]);
    }

    /**
     * GET /api/plan/history-restriction
     * Retorna informacoes sobre restricao de historico
     */
    public function historyRestriction(): Response
    {
        $userId = $this->requireApiUserIdAndReleaseSessionOrFail();
        $restriction = $this->limitService->getHistoryRestriction($userId);

        return Response::successResponse($restriction);
    }
}
