<?php

declare(strict_types=1);

namespace Application\Services\AI;

use Application\Models\AiChatMessage;
use Application\Models\Usuario;
use Application\Services\Plan\FeatureGate;
use Carbon\Carbon;

/**
 * Serviço de controle de quotas de IA por plano.
 * Verifica se o usuário pode usar IA e quantas mensagens restam.
 */
final class AIQuotaService
{
    /**
     * Verifica se o plano do usuário permite acesso ao chat de IA.
     */
    public static function canUseAI(Usuario $user): bool
    {
        return FeatureGate::allows($user, 'ai_chat');
    }

    /**
     * Verifica se o usuário ainda tem quota de mensagens IA no mês corrente.
     * Retorna true se tem quota restante ou se é ilimitado (ultra).
     */
    public static function hasQuotaRemaining(Usuario $user): bool
    {
        $limit = FeatureGate::limit($user, 'ai_messages_per_month');

        // null = ilimitado
        if ($limit === null) {
            return true;
        }

        // 0 = bloqueado (free)
        if ($limit === 0) {
            return false;
        }

        $used = self::getMonthlyUsage($user->id);
        return $used < $limit;
    }

    /**
     * Retorna o uso detalhado de IA do usuário no mês corrente.
     */
    public static function getUsage(Usuario $user): array
    {
        $limit = FeatureGate::limit($user, 'ai_messages_per_month');
        $used  = self::getMonthlyUsage($user->id);
        $tier  = FeatureGate::planTier($user);

        return [
            'plan'       => $tier,
            'can_use'    => self::canUseAI($user),
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
     * Conta mensagens do usuário (role=user) no mês corrente na tabela ai_chat_messages.
     */
    private static function getMonthlyUsage(int $userId): int
    {
        $startOfMonth = Carbon::now()->startOfMonth();

        return (int) AiChatMessage::whereHas('conversation', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })
            ->where('role', 'user')
            ->where('created_at', '>=', $startOfMonth)
            ->count();
    }
}
