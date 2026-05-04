<?php

declare(strict_types=1);

namespace Application\Middlewares;

use Application\Core\Exceptions\HttpResponseException;
use Application\Core\Request;
use Application\Core\Response;
use Application\Lib\Auth;
use Application\Services\AI\AIQuotaService;

/**
 * Middleware que verifica quota de IA antes de processar requisições.
 * Aplicar nas rotas /api/ai/* que consomem mensagens de IA.
 *
 * Detecta automaticamente o bucket (chat vs categorization) pela URI:
 *  - /api/ai/suggest-category -> bucket "categorization"
 *  - demais endpoints         -> bucket "chat"
 *
 * Retorna:
 * - 403 se o plano não tem a feature ai_chat habilitada -> upgrade_required
 * - 429 se a quota mensal do bucket foi esgotada -> quota_exceeded
 */
final class AIQuotaMiddleware
{
    public static function handle(?Request $request = null): void
    {
        $user = Auth::user();
        if (!$user) {
            throw new HttpResponseException(Response::unauthorizedResponse('Não autenticado'));
        }

        $plan = $user->plan();

        if (!AIQuotaService::canUseAI($user)) {
            $requiredTier = $plan->upgradeTarget() ?? 'pro';
            $requiredLabel = $requiredTier === 'ultra' ? 'Ultra' : 'Pro';

            throw new HttpResponseException(Response::jsonResponse([
                'success' => false,
                'upgrade_required' => true,
                'plan_needed' => $requiredTier,
                'message' => "O assistente IA está disponível a partir do plano {$requiredLabel}.",
            ], 403));
        }

        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $bucket = str_contains($uri, 'suggest-category') ? 'categorization' : 'chat';

        if (!AIQuotaService::hasQuotaRemaining($user, $bucket)) {
            $usage = AIQuotaService::getUsage($user);

            $bucketData = $bucket === 'categorization'
                ? $usage['categorization']
                : $usage['chat'];

            $upgradeTarget = $plan->upgradeTarget() ?? 'ultra';
            $upgradeLabel = $upgradeTarget === 'ultra' ? 'Ultra' : 'Pro';
            $bucketLabel = $bucket === 'categorization'
                ? 'sugestões de categoria com IA'
                : 'mensagens com IA';

            throw new HttpResponseException(Response::jsonResponse([
                'success' => false,
                'quota_exceeded' => true,
                'bucket' => $bucket,
                'upgrade_to' => $upgradeTarget,
                'usage' => $usage,
                'message' => "Você atingiu o limite de {$bucketData['limit']} {$bucketLabel} este mês. Faça upgrade para o {$upgradeLabel} e tenha IA ilimitada.",
            ], 429));
        }
    }
}
