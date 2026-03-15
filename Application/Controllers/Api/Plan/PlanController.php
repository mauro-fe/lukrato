<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Plan;

use Application\Core\Response;
use Application\Lib\Auth;
use Application\Services\Plan\PlanLimitService;
use Application\Services\Infrastructure\LogService;
use Application\Enums\LogCategory;

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
            Response::error('Não autenticado', 401);
            return;
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        try {
            $summary = $this->limitService->getLimitsSummary($userId);

            Response::success($summary);
        } catch (\Throwable $e) {
            LogService::captureException($e, LogCategory::SUBSCRIPTION, [
                'action' => 'get_plan_limits',
                'user_id' => $userId,
            ], $userId);

            // Retornar resposta padrão com limites do plano free para não liberar acesso indevido
            $freeConfig = $this->limitService->getConfig()['limits']['free'] ?? [];
            Response::success(
                [
                    'plan' => 'free',
                    'is_pro' => false,
                    'contas' => ['allowed' => true, 'limit' => $freeConfig['max_contas'] ?? 2, 'used' => 0],
                    'cartoes' => ['allowed' => true, 'limit' => $freeConfig['max_cartoes'] ?? 1, 'used' => 0],
                    'categorias' => ['allowed' => true, 'limit' => $freeConfig['max_categorias_custom'] ?? 10, 'used' => 0],
                    'metas' => ['allowed' => true, 'limit' => $freeConfig['max_metas'] ?? 2, 'used' => 0],
                    'historico' => ['restricted' => true, 'months_limit' => $freeConfig['historico_meses'] ?? 3],
                    'features' => [],
                    'upgrade_url' => '/assinatura',
                ]
            );
        }
    }

    /**
     * GET /api/plan/features
     * Retorna as features disponíveis para o usuário
     */
    public function features(): void
    {
        $userId = Auth::id();

        if (!$userId) {
            Response::error('Não autenticado', 401);
            return;
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $isPro = $this->limitService->isPro($userId);
        $features = $this->limitService->getFeatures($userId);

        Response::success([
            'plan' => $isPro ? 'pro' : 'free',
            'is_pro' => $isPro,
            'features' => $features,
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
            Response::error('Não autenticado', 401);
            return;
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
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

        Response::success(['resource' => $resource, ...$result]);
    }

    /**
     * GET /api/plan/history-restriction
     * Retorna informações sobre restrição de histórico
     */
    public function historyRestriction(): void
    {
        $userId = Auth::id();

        if (!$userId) {
            Response::error('Não autenticado', 401);
            return;
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $restriction = $this->limitService->getHistoryRestriction($userId);

        Response::success($restriction);
    }
}
