<?php

declare(strict_types=1);

namespace Application\Services\AI\Collectors;

use Application\Models\Usuario;
use Application\Services\AI\DTO\ContextPeriod;
use Application\Services\AI\Interfaces\ContextCollectorInterface;

class UsuariosCollector implements ContextCollectorInterface
{
    public function collect(ContextPeriod $period, ?int $userId = null): array
    {
        // Dados globais de usuários são admin-only
        if ($userId !== null) {
            return [];
        }

        $total        = Usuario::count();
        $admins       = Usuario::where('is_admin', 1)->count();
        $novosMes     = Usuario::whereBetween('created_at', [$period->inicioMes, $period->fimMes])->count();
        $novosMesAnt  = Usuario::whereBetween('created_at', [$period->inicioMesAnterior, $period->fimMesAnterior])->count();
        $crescimento  = $novosMesAnt > 0
            ? round((($novosMes - $novosMesAnt) / $novosMesAnt) * 100, 1)
            : ($novosMes > 0 ? 100 : 0);

        $verificados  = Usuario::whereNotNull('email_verified_at')->count();

        return [
            'usuarios' => [
                'total'                    => $total,
                'administradores'          => $admins,
                'novos_este_mes'           => $novosMes,
                'novos_mes_anterior'       => $novosMesAnt,
                'crescimento_percentual'   => $crescimento,
                'emails_verificados'       => $verificados,
                'taxa_verificacao'         => $total > 0 ? round(($verificados / $total) * 100, 1) : 0,
            ],
        ];
    }
}
