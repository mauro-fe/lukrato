<?php

namespace Application\Services\Plan;

use Application\Models\Usuario;

/**
 * Feature gate centralizado — usa Billing.php como fonte de verdade.
 * Delega a verificação de plano para Usuario::isPro().
 */
final class FeatureGate
{
    private static ?array $config = null;

    /**
     * Carrega a configuração de billing (com cache estático).
     */
    private static function config(): array
    {
        if (self::$config === null) {
            self::$config = require __DIR__ . '/../../Config/Billing.php';
        }
        return self::$config;
    }

    /**
     * Verifica se o usuário tem acesso a uma feature.
     * Usa Billing.php como fonte de verdade.
     */
    public static function allows(Usuario $u, string $feature): bool
    {
        $plan = $u->isPro() ? 'pro' : 'free';
        $config = self::config();
        return (bool) ($config['features'][$plan][$feature] ?? false);
    }

    /**
     * Retorna o limite de um recurso para o usuário.
     * Usa Billing.php como fonte de verdade.
     * Retorna null se ilimitado (pro).
     */
    public static function limit(Usuario $u, string $key): ?int
    {
        $plan = $u->isPro() ? 'pro' : 'free';
        $config = self::config();
        $value = $config['limits'][$plan][$key] ?? null;
        return $value !== null ? (int) $value : null;
    }
}
