<?php

declare(strict_types=1);

namespace Application\Middlewares;

use Application\Core\Response;
use Application\Lib\Auth;
use Application\Services\AI\AIQuotaService;

/**
 * Middleware que verifica quota de IA antes de processar requisições.
 * Aplicar nas rotas /api/ai/* que consomem mensagens de IA.
 *
 * Retorna:
 * - 403 se o plano não permite IA (free) → upgrade_required
 * - 429 se a quota mensal foi esgotada (pro) → quota_exceeded
 */
final class AIQuotaMiddleware
{
    public static function handle(): void
    {
        $user = Auth::user();
        if (!$user) {
            Response::unauthorized('Não autenticado');
            exit;
        }

        // Plano não permite IA (free)
        if (!AIQuotaService::canUseAI($user)) {
            Response::json([
                'success'          => false,
                'upgrade_required' => true,
                'plan_needed'      => 'pro',
                'message'          => 'O assistente IA está disponível a partir do plano Pro.',
            ], 403);
            exit;
        }

        // Quota mensal esgotada (pro com limite)
        if (!AIQuotaService::hasQuotaRemaining($user)) {
            $usage = AIQuotaService::getUsage($user);

            Response::json([
                'success'        => false,
                'quota_exceeded' => true,
                'upgrade_to'     => 'ultra',
                'usage'          => $usage,
                'message'        => "Você atingiu o limite de {$usage['limit']} mensagens com IA este mês. Faça upgrade para o Ultra e tenha IA ilimitada.",
            ], 429);
            exit;
        }
    }
}
