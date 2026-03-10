<?php

declare(strict_types=1);

namespace Application\Services\AI;

use Application\Models\AiLog;
use Application\Models\Usuario;
use Application\Services\Plan\FeatureGate;
use Carbon\Carbon;

/**
 * Serviço de controle de quotas de IA por plano.
 *
 * Dois buckets independentes:
 *  - chat: mensagens de chat, análise, extração (ai_logs.type IN chat, analyze_spending, extract_transaction)
 *  - categorization: sugestão de categoria (ai_logs.type = suggest_category)
 *
 * Contagem via tabela ai_logs (unifica web + WhatsApp + API).
 */
final class AIQuotaService
{
    /** Tipos de ai_logs que contam no bucket "chat" */
    private const CHAT_TYPES = ['chat', 'analyze_spending', 'extract_transaction'];

    /** Tipos de ai_logs que contam no bucket "categorization" */
    private const CATEGORIZATION_TYPES = ['suggest_category'];

    /**
     * Verifica se o plano do usuário permite acesso ao chat de IA.
     */
    public static function canUseAI(Usuario $user): bool
    {
        return FeatureGate::allows($user, 'ai_chat');
    }

    /**
     * Verifica se o usuário ainda tem quota no bucket especificado.
     *
     * @param string $bucket 'chat' | 'categorization'
     */
    public static function hasQuotaRemaining(Usuario $user, string $bucket = 'chat'): bool
    {
        $limitKey = $bucket === 'categorization'
            ? 'ai_categorization_per_month'
            : 'ai_messages_per_month';

        $limit = FeatureGate::limit($user, $limitKey);

        // null = ilimitado
        if ($limit === null) {
            return true;
        }

        // 0 = bloqueado
        if ($limit === 0) {
            return false;
        }

        $used = self::getMonthlyUsageByType($user->id, $bucket);
        return $used < $limit;
    }

    /**
     * Retorna o uso detalhado de IA do usuário com buckets separados.
     */
    public static function getUsage(Usuario $user): array
    {
        $tier = FeatureGate::planTier($user);

        return [
            'plan'           => $tier,
            'can_use'        => self::canUseAI($user),
            'chat'           => self::getBucketUsage($user, 'chat'),
            'categorization' => self::getBucketUsage($user, 'categorization'),
        ];
    }

    /**
     * Retorna uso detalhado de um bucket específico.
     */
    private static function getBucketUsage(Usuario $user, string $bucket): array
    {
        $limitKey = $bucket === 'categorization'
            ? 'ai_categorization_per_month'
            : 'ai_messages_per_month';

        $limit = FeatureGate::limit($user, $limitKey);
        $used  = self::getMonthlyUsageByType($user->id, $bucket);

        return [
            'used'       => $used,
            'limit'      => $limit,
            'remaining'  => $limit !== null ? max(0, $limit - $used) : null,
            'unlimited'  => $limit === null,
            'percentage' => $limit !== null && $limit > 0
                ? min(100, round(($used / $limit) * 100))
                : null,
        ];
    }

    /**
     * Conta operações do usuário no mês corrente via ai_logs.
     */
    private static function getMonthlyUsageByType(int $userId, string $bucket): int
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $types = $bucket === 'categorization' ? self::CATEGORIZATION_TYPES : self::CHAT_TYPES;

        return (int) AiLog::where('user_id', $userId)
            ->whereIn('type', $types)
            ->where('success', true)
            ->where('created_at', '>=', $startOfMonth)
            ->count();
    }
}
