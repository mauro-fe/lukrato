<?php

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int|null $blog_categoria_id
 * @property string $titulo
 * @property string $slug
 * @property string|null $resumo
 * @property string $conteudo
 * @property string|null $imagem_capa
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property int|null $tempo_leitura
 * @property string $status
 * @property \Carbon\Carbon|null $published_at
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder publicados()
 * @method static \Illuminate\Database\Eloquent\Builder byCategoria(int $categoriaId)
 * @method static \Illuminate\Database\Eloquent\Builder recentes()
 * @method static \Illuminate\Database\Eloquent\Builder bySlug(string $slug)
 *
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class BlogPost extends Model
{
    protected $table = 'blog_posts';

    protected $fillable = [
        'blog_categoria_id',
        'titulo',
        'slug',
        'resumo',
        'conteudo',
        'imagem_capa',
        'meta_title',
        'meta_description',
        'tempo_leitura',
        'status',
        'published_at',
    ];

    protected $casts = [
        'blog_categoria_id' => 'int',
        'tempo_leitura'     => 'int',
        'published_at'      => 'datetime',
    ];

    // ─── Relacionamentos ────────────────────────────────

    /**
     * Categoria do post.
     */
    public function categoria()
    {
        return $this->belongsTo(BlogCategoria::class, 'blog_categoria_id');
    }

    // ─── Scopes ─────────────────────────────────────────

    /**
     * Apenas posts publicados (status = published e published_at no passado).
     */
    public function scopePublicados($query)
    {
        return $query->where('status', 'published')
            ->where('published_at', '<=', now());
    }

    /**
     * Filtrar por categoria.
     */
    public function scopeByCategoria($query, int $categoriaId)
    {
        return $query->where('blog_categoria_id', $categoriaId);
    }

    /**
     * Ordenar por data de publicação mais recente.
     */
    public function scopeRecentes($query)
    {
        return $query->orderBy('published_at', 'desc');
    }

    /**
     * Buscar por slug.
     */
    public function scopeBySlug($query, string $slug)
    {
        return $query->where('slug', $slug);
    }

    // ─── Accessors ──────────────────────────────────────

    /**
     * URL pública do post.
     */
    public function getUrlAttribute(): string
    {
        return rtrim(BASE_URL, '/') . '/aprenda/' . $this->slug;
    }

    /**
     * URL completa da imagem de capa.
     */
    public function getImagemCapaUrlAttribute(): ?string
    {
        if (empty($this->imagem_capa)) {
            return null;
        }
        return rtrim(BASE_URL, '/') . '/' . ltrim($this->imagem_capa, '/');
    }

    /**
     * Verifica se o post está publicado.
     */
    public function getIsPublishedAttribute(): bool
    {
        return $this->status === 'published'
            && $this->published_at !== null
            && $this->published_at->lte(now());
    }

    /**
     * Retorna o meta_title efetivo (falls back to titulo).
     */
    public function getEffectiveMetaTitleAttribute(): string
    {
        return !empty($this->meta_title)
            ? $this->meta_title
            : $this->titulo . ' | Lukrato';
    }

    /**
     * Retorna a meta_description efetiva (falls back to resumo).
     */
    public function getEffectiveMetaDescriptionAttribute(): string
    {
        if (!empty($this->meta_description)) {
            return $this->meta_description;
        }
        if (!empty($this->resumo)) {
            return mb_substr(strip_tags($this->resumo), 0, 160);
        }
        return mb_substr(strip_tags($this->conteudo), 0, 160);
    }
}
