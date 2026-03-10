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
 * - 403 se o plano não tem a feature ai_chat habilitada → upgrade_required
 * - 429 se a quota mensal de mensagens foi esgotada → quota_exceeded
 *
 * Nota: Free tem ai_chat=true com limite de 5 msgs/mês (degustação).
 *       Pro tem ai_chat=true com limite ilimitado.
 *       Ultra tem ai_chat=true com limite ilimitado.
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

        // Feature ai_chat desabilitada no plano
        if (!AIQuotaService::canUseAI($user)) {
            Response::json([
                'success'          => false,
                'upgrade_required' => true,
                'plan_needed'      => 'pro',
                'message'          => 'O assistente IA está disponível a partir do plano Pro.',
            ], 403);
            exit;
        }

        // Quota mensal esgotada (free=5/mês, pro/ultra=ilimitado)
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
