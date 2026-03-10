<?php

namespace Application\Services\Plan;

use Application\Models\Usuario;

/**
 * Feature gate centralizado — usa Billing.php como fonte de verdade.
 * Suporta 3 tiers: free, pro, ultra.
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
     * Resolve o tier do plano do usuário: 'free', 'pro' ou 'ultra'.
     */
    public static function planTier(Usuario $u): string
    {
        $code = strtolower((string) ($u->planoAtual()?->code ?? ''));

        if ($code === 'ultra') {
            return 'ultra';
        }

        // Qualquer plano pago que não seja free/gratuito/ultra → pro
        if ($u->isPro() && $code !== 'ultra') {
            return 'pro';
        }

        // Fallback: código desconhecido cai para free
        if ($code !== '' && $code !== 'free' && $code !== 'gratuito') {
            error_log("[FeatureGate] Código de plano desconhecido '{$code}' para user #{$u->id} — fallback para 'free'");
        }

        return 'free';
    }

    /**
     * Verifica se o usuário tem acesso a uma feature.
     * Usa Billing.php como fonte de verdade.
     */
    public static function allows(Usuario $u, string $feature): bool
    {
        $plan = self::planTier($u);
        $config = self::config();
        return (bool) ($config['features'][$plan][$feature] ?? false);
    }

    /**
     * Retorna o limite de um recurso para o usuário.
     * Usa Billing.php como fonte de verdade.
     * Retorna null se ilimitado.
     */
    public static function limit(Usuario $u, string $key): ?int
    {
        $plan = self::planTier($u);
        $config = self::config();
        $value = $config['limits'][$plan][$key] ?? null;
        return $value !== null ? (int) $value : null;
    }

    /**
     * Retorna uma mensagem da config de billing.
     */
    public static function message(string $key, array $vars = []): string
    {
        $config = self::config();
        $msg = $config['messages'][$key] ?? '';
        foreach ($vars as $k => $v) {
            $msg = str_replace("{{$k}}", (string) $v, $msg);
        }
        return $msg;
    }
}
