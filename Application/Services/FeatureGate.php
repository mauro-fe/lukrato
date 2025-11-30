<?php

namespace Application\Services;

use Application\Models\Usuario;

final class FeatureGate
{
    private static array $entitlements = [
        'gratuito' => [
            'reports' => false,
            'scheduling' => false,
            'export' => false,
            'limits' => ['lancamentos_mes' => 200, 'contas' => 3, 'categorias' => 5],
        ],
        'pro' => [
            'reports' => true,
            'scheduling' => true,
            'export' => true,
            'limits' => ['lancamentos_mes' => null, 'contas' => null, 'categorias' => null],
        ],
    ];

    public static function allows(Usuario $u, string $feature): bool
    {
        $plano = $u->planoAtual();
        $code  = $plano?->code ?? ($u->plano ?? 'gratuito');

        $map = [
            'gratuito' => ['reports' => false, 'scheduling' => false, 'export' => false],
            'free'     => ['reports' => false, 'scheduling' => false, 'export' => false],
            'pro'      => ['reports' => true, 'scheduling' => true, 'export' => true],
        ];
        return (bool) ($map[$code][$feature] ?? false);
    }

    public static function limit(Usuario $u, string $key): ?int
    {
        $plan = $u->plano ?: 'gratuito';
        return self::$entitlements[$plan]['limits'][$key] ?? null;
    }
}