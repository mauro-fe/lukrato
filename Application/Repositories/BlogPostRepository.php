<?php

declare(strict_types=1);

namespace Application\Repositories;

use Application\Models\BlogPost;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends BaseRepository<BlogPost>
 */
class BlogPostRepository extends BaseRepository
{
    protected function getModelClass(): string
    {
        return BlogPost::class;
    }

    /**
     * Busca post por slug.
     */
    public function findBySlug(string $slug): ?BlogPost
    {
        return $this->query()->where('slug', $slug)->first();
    }

    /**
     * Busca post publicado por slug.
     */
    public function findPublishedBySlug(string $slug): ?BlogPost
    {
        return $this->query()
            ->publicados()
            ->bySlug($slug)
            ->with('categoria')
            ->first();
    }

    /**
     * Lista posts publicados com paginação.
     */
    public function listPublished(int $perPage = 12, int $page = 1): array
    {
        $query = $this->query()
            ->publicados()
            ->recentes()
            ->with('categoria');

        $total = $query->count();

        $items = $query
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        return [
            'items'   => $items,
            'total'   => $total,
            'page'    => $page,
            'perPage' => $perPage,
        ];
    }

    /**
     * Lista posts publicados por categoria.
     */
    public function listByCategoria(int $categoriaId, int $perPage = 12, int $page = 1): array
    {
        $query = $this->query()
            ->publicados()
            ->byCategoria($categoriaId)
            ->recentes()
            ->with('categoria');

        $total = $query->count();

        $items = $query
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        return [
            'items'   => $items,
            'total'   => $total,
            'page'    => $page,
            'perPage' => $perPage,
        ];
    }

    /**
     * Lista posts para admin com filtros e paginação.
     */
    public function paginateAdmin(array $filters = [], int $perPage = 15, int $page = 1): array
    {
        $query = $this->query()->with('categoria');

        // Filtro por status
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filtro por categoria
        if (!empty($filters['blog_categoria_id'])) {
            $query->where('blog_categoria_id', (int) $filters['blog_categoria_id']);
        }

        // Filtro por busca (título ou resumo)
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('titulo', 'LIKE', $search)
                    ->orWhere('resumo', 'LIKE', $search);
            });
        }

        $total = $query->count();

        $items = $query
            ->orderBy('created_at', 'desc')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        return [
            'items'   => $items,
            'total'   => $total,
            'page'    => $page,
            'perPage' => $perPage,
        ];
    }

    /**
     * Conta posts por status.
     */
    public function countByStatus(): array
    {
        $total     = $this->query()->count();
        $published = $this->query()->where('status', 'published')->count();
        $draft     = $this->query()->where('status', 'draft')->count();

        return [
            'total'     => $total,
            'published' => $published,
            'draft'     => $draft,
        ];
    }

    /**
     * Busca posts relacionados (mesma categoria, excluindo o atual).
     */
    public function findRelated(int $postId, ?int $categoriaId, int $limit = 4): Collection
    {
        $query = $this->query()
            ->publicados()
            ->where('id', '!=', $postId)
            ->recentes()
            ->limit($limit);

        if ($categoriaId) {
            $query->where('blog_categoria_id', $categoriaId);
        }

        return $query->get();
    }
}
