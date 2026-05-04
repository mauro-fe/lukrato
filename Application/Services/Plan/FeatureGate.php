<?php

declare(strict_types=1);

namespace Application\Services\Plan;

use Application\Models\Usuario;

/**
 * Feature gate centralizado — usa Billing.php como fonte de verdade.
 * Suporta 3 tiers: free, pro, ultra.
 */
final class FeatureGate
{
    /**
     * Resolve o tier do plano do usuário: 'free', 'pro' ou 'ultra'.
     */
    public static function planTier(Usuario $u): string
    {
        return $u->plan()->tier();
    }

    /**
     * Verifica se o usuário tem acesso a uma feature.
     * Usa Billing.php como fonte de verdade.
     */
    public static function allows(Usuario $u, string $feature): bool
    {
        return $u->plan()->allows($feature);
    }

    /**
     * Retorna o limite de um recurso para o usuário.
     * Usa Billing.php como fonte de verdade.
     * Retorna null se ilimitado.
     */
    public static function limit(Usuario $u, string $key): ?int
    {
        return $u->plan()->limit($key);
    }

    /**
     * Retorna uma mensagem da config de billing.
     */
    public static function message(string $key, array $vars = []): string
    {
        return PlanContext::message($key, $vars);
    }
}
