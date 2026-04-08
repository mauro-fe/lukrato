<?php

declare(strict_types=1);

namespace Application\Services\AI\Helpers;

use Application\Container\ApplicationContainer;
use Application\Models\Categoria;
use Application\Services\Infrastructure\CacheService;

/**
 * Carrega categorias e subcategorias do usuário para uso nos prompts da IA.
 * Retorna no formato flat: ["Alimentação", "Alimentação > Delivery", "Transporte", "Transporte > Uber", ...]
 *
 * Usa cache (1h TTL) para evitar queries repetidas.
 */
class UserCategoryLoader
{
    private const CACHE_TTL = 3600; // 1 hora
    private const CACHE_PREFIX = 'ai:user_categories:';

    /**
     * Carrega todas as categorias + subcategorias do usuário.
     *
     * @return string[] Ex: ["Alimentação", "Alimentação > Delivery", "Alimentação > Supermercado", "Transporte", ...]
     */
    public static function load(int $userId): array
    {
        try {
            $cache = self::cache();
            $cacheKey = self::CACHE_PREFIX . $userId;

            $cached = $cache->get($cacheKey);
            if (is_array($cached) && !empty($cached)) {
                return $cached;
            }

            $result = self::fetchFromDatabase($userId);

            if (!empty($result)) {
                $cache->set($cacheKey, $result, self::CACHE_TTL);
            }

            return $result;
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Retorna apenas nomes de categorias raiz (sem subcategorias).
     * Útil quando o prompt precisa só de categorias principais.
     *
     * @return string[]
     */
    public static function loadRootOnly(int $userId): array
    {
        try {
            return Categoria::query()
                ->whereNull('parent_id')
                ->where(function ($q) use ($userId) {
                    $q->whereNull('user_id')->orWhere('user_id', $userId);
                })
                ->pluck('nome')
                ->unique()
                ->values()
                ->toArray();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Invalida o cache de categorias do usuário.
     * Chamar quando categorias/subcategorias são criadas, editadas ou removidas.
     */
    public static function invalidate(int $userId): void
    {
        try {
            $cache = self::cache();
            $cache->forget(self::CACHE_PREFIX . $userId);
        } catch (\Throwable) {
            // Falha silenciosa — próxima chamada vai buscar do banco
        }
    }

    /**
     * Busca categorias + subcategorias do banco de dados.
     *
     * @return string[]
     */
    private static function fetchFromDatabase(int $userId): array
    {
        $categories = Categoria::query()
            ->whereNull('parent_id')
            ->where(function ($q) use ($userId) {
                $q->whereNull('user_id')->orWhere('user_id', $userId);
            })
            ->with(['subcategorias' => function ($q) use ($userId) {
                $q->where(function ($sq) use ($userId) {
                    $sq->whereNull('user_id')->orWhere('user_id', $userId);
                });
            }])
            ->orderBy('nome')
            ->get();

        $result = [];

        foreach ($categories as $cat) {
            $result[] = $cat->nome;

            foreach ($cat->subcategorias as $sub) {
                $result[] = $cat->nome . ' > ' . $sub->nome;
            }
        }

        return array_values(array_unique($result));
    }

    private static function cache(): CacheService
    {
        /** @var CacheService $cache */
        $cache = ApplicationContainer::resolveOrNew(null, CacheService::class);

        return $cache;
    }
}
