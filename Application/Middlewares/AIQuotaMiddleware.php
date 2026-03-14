<?php

declare(strict_types=1);

namespace Application\Middlewares;

use Application\Core\Request;
use Application\Core\Response;
use Application\Lib\Auth;
use Application\Services\AI\AIQuotaService;
use Application\Services\Plan\FeatureGate;

/**
 * Middleware que verifica quota de IA antes de processar requisições.
 * Aplicar nas rotas /api/ai/* que consomem mensagens de IA.
 *
 * Detecta automaticamente o bucket (chat vs categorization) pela URI:
 *  - /api/ai/suggest-category → bucket "categorization"
 *  - demais endpoints          → bucket "chat"
 *
 * Retorna:
 * - 403 se o plano não tem a feature ai_chat habilitada → upgrade_required
 * - 429 se a quota mensal do bucket foi esgotada → quota_exceeded
 */
final class AIQuotaMiddleware
{
    public static function handle(?Request $request = null): void
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

        // Detectar bucket pela URI
        $uri    = $_SERVER['REQUEST_URI'] ?? '';
        $bucket = str_contains($uri, 'suggest-category') ? 'categorization' : 'chat';

        // Quota mensal esgotada
        if (!AIQuotaService::hasQuotaRemaining($user, $bucket)) {
            $usage = AIQuotaService::getUsage($user);
            $tier  = FeatureGate::planTier($user);

            $bucketData = $bucket === 'categorization'
                ? $usage['categorization']
                : $usage['chat'];

            $upgradeTarget = $tier === 'free' ? 'pro' : 'ultra';
            $bucketLabel   = $bucket === 'categorization'
                ? 'sugestões de categoria com IA'
                : 'mensagens com IA';

            Response::json([
                'success'        => false,
                'quota_exceeded' => true,
                'bucket'         => $bucket,
                'upgrade_to'     => $upgradeTarget,
                'usage'          => $usage,
                'message'        => "Você atingiu o limite de {$bucketData['limit']} {$bucketLabel} este mês. Faça upgrade para o " . ucfirst($upgradeTarget) . " e tenha IA ilimitada.",
            ], 429);
            exit;
        }
    }
}
