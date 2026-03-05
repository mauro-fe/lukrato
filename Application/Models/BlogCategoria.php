<?php

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $nome
 * @property string $slug
 * @property string|null $icone
 * @property int $ordem
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder ordenadas()
 *
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class BlogCategoria extends Model
{
    protected $table = 'blog_categorias';

    protected $fillable = [
        'nome',
        'slug',
        'icone',
        'ordem',
    ];

    protected $casts = [
        'ordem' => 'int',
    ];

    // ─── Relacionamentos ────────────────────────────────

    /**
     * Posts desta categoria.
     */
    public function posts()
    {
        return $this->hasMany(BlogPost::class, 'blog_categoria_id');
    }

    /**
     * Posts publicados desta categoria.
     */
    public function postsPublicados()
    {
        return $this->hasMany(BlogPost::class, 'blog_categoria_id')
            ->where('status', 'published')
            ->where('published_at', '<=', now());
    }

    // ─── Scopes ─────────────────────────────────────────

    /**
     * Ordenar por campo 'ordem'.
     */
    public function scopeOrdenadas($query)
    {
        return $query->orderBy('ordem', 'asc');
    }

    // ─── Accessors ──────────────────────────────────────

    /**
     * URL pública da categoria.
     */
    public function getUrlAttribute(): string
    {
        return rtrim(BASE_URL, '/') . '/blog/categoria/' . $this->slug;
    }
}
