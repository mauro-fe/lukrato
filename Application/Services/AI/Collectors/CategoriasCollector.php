<?php

declare(strict_types=1);

namespace Application\Services\AI\Collectors;

use Application\Models\Categoria;
use Application\Services\AI\DTO\ContextPeriod;
use Application\Services\AI\Interfaces\ContextCollectorInterface;
use Illuminate\Database\Capsule\Manager as DB;

class CategoriasCollector implements ContextCollectorInterface
{
    public function collect(ContextPeriod $period): array
    {
        $total         = Categoria::count();
        $pais          = Categoria::whereNull('parent_id')->count();
        $subcategorias = Categoria::whereNotNull('parent_id')->count();
        $personalizadas = Categoria::where('is_seeded', 0)->count();
        $padrao        = Categoria::where('is_seeded', 1)->count();

        $porTipo = Categoria::whereNull('parent_id')
            ->select('tipo', DB::raw('COUNT(*) as qtd'))
            ->groupBy('tipo')
            ->get()
            ->mapWithKeys(fn($r) => [$r->tipo => (int) $r->qtd])
            ->toArray();

        $usersComCustom = Categoria::where('is_seeded', 0)
            ->whereNotNull('user_id')
            ->distinct('user_id')
            ->count('user_id');

        return [
            'categorias' => [
                'total'                         => $total,
                'categorias_pai'                => $pais,
                'subcategorias'                 => $subcategorias,
                'personalizadas_pelo_usuario'   => $personalizadas,
                'padrao_sistema'                => $padrao,
                'por_tipo'                      => $porTipo,
                'usuarios_com_cat_personalizada' => $usersComCustom,
            ],
        ];
    }
}
