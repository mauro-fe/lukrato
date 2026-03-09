<?php

declare(strict_types=1);

namespace Application\Services\AI\Collectors;

use Application\Models\PasswordReset;
use Application\Models\Usuario;
use Application\Services\AI\DTO\ContextPeriod;
use Application\Services\AI\Interfaces\ContextCollectorInterface;
use Illuminate\Database\Capsule\Manager as DB;

class SegurancaCollector implements ContextCollectorInterface
{
    public function collect(ContextPeriod $period, ?int $userId = null): array
    {
        if ($userId !== null) return [];

        $resetsSemana  = PasswordReset::where('created_at', '>=', $period->now->copy()->subWeek())->count();
        $resetsUsados  = PasswordReset::whereNotNull('used_at')->count();
        $resetsTotal   = PasswordReset::count();

        $googleUsers   = Usuario::whereNotNull('google_id')->count();
        $totalUsers    = Usuario::count();

        $contasDeletadas = Usuario::onlyTrashed()->count();

        $loginRecente = Usuario::whereNotNull('last_login_ip')
            ->select(DB::raw('COUNT(DISTINCT last_login_ip) as ips_distintos'))
            ->value('ips_distintos');

        return [
            'seguranca' => [
                'resets_senha_ultima_semana' => $resetsSemana,
                'resets_usados_total'        => $resetsUsados,
                'resets_total'               => $resetsTotal,
                'usuarios_google_login'      => $googleUsers,
                'taxa_google_login'          => $totalUsers > 0 ? round(($googleUsers / $totalUsers) * 100, 1) : 0,
                'contas_deletadas'           => $contasDeletadas,
                'ips_login_distintos'        => (int) ($loginRecente ?? 0),
            ],
        ];
    }
}
