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
 *  - chat: qualquer operação de IA fora da categorização que tenha consumido LLM
 *  - categorization: sugestão de categoria com consumo efetivo de LLM
 *
 * Contagem via tabela ai_logs (unifica web + canais externos), considerando apenas
 * execuções bem-sucedidas com `source = llm` ou `tokens_total > 0`.
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
        $query = AiLog::where('user_id', $userId)
            ->where('success', true)
            ->where('created_at', '>=', $startOfMonth);

        if ($bucket === 'categorization') {
            $query->where('type', 'suggest_category');
        } else {
            $query->where('type', '!=', 'suggest_category');
        }

        $query->where(function ($q) {
            $q->where('source', 'llm')
                ->orWhere('tokens_total', '>', 0);
        });

        return (int) $query->count();
    }
}
