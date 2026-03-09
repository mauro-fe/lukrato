<?php

declare(strict_types=1);

namespace Application\Services\AI\Collectors;

use Application\Models\AssinaturaUsuario;
use Application\Services\AI\DTO\ContextPeriod;
use Application\Services\AI\Interfaces\ContextCollectorInterface;
use Illuminate\Database\Capsule\Manager as DB;

class AssinaturasCollector implements ContextCollectorInterface
{
    public function collect(ContextPeriod $period, ?int $userId = null): array
    {
        $base = AssinaturaUsuario::query();
        if ($userId) $base->where('user_id', $userId);

        $total  = (clone $base)->count();
        $ativas = (clone $base)->where('status', AssinaturaUsuario::ST_ACTIVE)->count();

        $porPlanoQuery = DB::table('assinaturas_usuarios')
            ->join('planos', 'assinaturas_usuarios.plano_id', '=', 'planos.id')
            ->where('assinaturas_usuarios.status', 'active');
        if ($userId) $porPlanoQuery->where('assinaturas_usuarios.user_id', $userId);

        $porPlano = $porPlanoQuery->select('planos.nome', DB::raw('COUNT(*) as total'))
            ->groupBy('planos.nome')
            ->get();

        $mrrQuery = DB::table('assinaturas_usuarios')
            ->join('planos', 'assinaturas_usuarios.plano_id', '=', 'planos.id')
            ->where('assinaturas_usuarios.status', 'active');
        if ($userId) $mrrQuery->where('assinaturas_usuarios.user_id', $userId);

        $mrr = (int) $mrrQuery->sum('planos.preco_centavos');

        return [
            'assinaturas' => [
                'total'  => $total,
                'ativas' => $ativas,
                'por_plano' => $porPlano->map(fn($r) => [
                    'plano' => $r->nome,
                    'total' => (int) $r->total,
                ])->toArray(),
                'mrr_centavos' => $mrr,
                'mrr_reais'    => round($mrr / 100, 2),
            ],
        ];
    }
}
