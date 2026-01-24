<?php

declare(strict_types=1);

namespace Application\Controllers\Api;

use Application\Core\Response;
use Application\Lib\Auth;
use Application\Services\PlanLimitService;

/**
 * Controller para consultar limites e status do plano do usuário
 */
class PlanController
{
    private PlanLimitService $limitService;

    public function __construct()
    {
        $this->limitService = new PlanLimitService();
    }

    /**
     * GET /api/plan/limits
     * Retorna todos os limites e uso atual do usuário
     */
    public function limits(): void
    {
        $userId = Auth::id();

        if (!$userId) {
            Response::json(['error' => 'Não autenticado'], 401);
            return;
        }

        $summary = $this->limitService->getLimitsSummary($userId);

        Response::json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    /**
     * GET /api/plan/features
     * Retorna as features disponíveis para o usuário
     */
    public function features(): void
    {
        $userId = Auth::id();

        if (!$userId) {
            Response::json(['error' => 'Não autenticado'], 401);
            return;
        }

        $isPro = $this->limitService->isPro($userId);
        $features = $this->limitService->getFeatures($userId);

        Response::json([
            'success' => true,
            'data' => [
                'plan' => $isPro ? 'pro' : 'free',
                'is_pro' => $isPro,
                'features' => $features,
            ],
        ]);
    }

    /**
     * GET /api/plan/can-create/{resource}
     * Verifica se o usuário pode criar um recurso específico
     */
    public function canCreate(string $resource): void
    {
        $userId = Auth::id();

        if (!$userId) {
            Response::json(['error' => 'Não autenticado'], 401);
            return;
        }

        $result = match ($resource) {
            'conta', 'contas', 'account', 'accounts'
            => $this->limitService->canCreateConta($userId),
            'cartao', 'cartoes', 'card', 'cards'
            => $this->limitService->canCreateCartao($userId),
            'categoria', 'categorias', 'category', 'categories'
            => $this->limitService->canCreateCategoria($userId),
            'meta', 'metas', 'goal', 'goals'
            => $this->limitService->canCreateMeta($userId),
            default => [
                'allowed' => true,
                'error' => 'Recurso não rastreado',
            ],
        };

        Response::json([
            'success' => true,
            'resource' => $resource,
            'data' => $result,
        ]);
    }

    /**
     * GET /api/plan/history-restriction
     * Retorna informações sobre restrição de histórico
     */
    public function historyRestriction(): void
    {
        $userId = Auth::id();

        if (!$userId) {
            Response::json(['error' => 'Não autenticado'], 401);
            return;
        }

        $restriction = $this->limitService->getHistoryRestriction($userId);

        Response::json([
            'success' => true,
            'data' => $restriction,
        ]);
    }
}
