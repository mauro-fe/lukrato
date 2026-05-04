<?php

namespace Application\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $nome
 * @property string|null $icone
 * @property string $tipo
 * @property int|null $user_id
 * @property int|null $parent_id
 * @property bool $is_seeded
 * @property int $ordem
 *
 * @method static Builder<Categoria> where(string $column, $operator = null, $value = null, string $boolean = 'and')
 * @method static Builder<Categoria> receitas()
 * @method static Builder<Categoria> despesas()
 * @method static Builder<Categoria> forUser(int $userId)
 * @method static Builder<Categoria> roots()
 * @method static Builder<Categoria> children()
 *
 * @mixin Builder<Categoria>
 */
class Categoria extends Model
{
    protected $table = 'categorias';
    protected $fillable = ['nome', 'icone', 'tipo', 'user_id', 'parent_id', 'is_seeded', 'ordem'];
    protected $casts = [
        'user_id'   => 'int',
        'parent_id' => 'int',
        'is_seeded' => 'bool',
    ];
    public $timestamps = false;

    // ─── Relacionamentos ────────────────────────────────

    /**
     * @return BelongsTo<Usuario, $this>
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'user_id');
    }

    /**
     * @return HasMany<Lancamento, $this>
     */
    public function lancamentos(): HasMany
    {
        return $this->hasMany(Lancamento::class, 'categoria_id');
    }

    /**
     * Categoria pai (se esta for uma subcategoria).
     */
    /**
     * @return BelongsTo<Categoria, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Subcategorias desta categoria.
     */
    /**
     * @return HasMany<Categoria, $this>
     */
    public function subcategorias(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('nome');
    }

    /**
     * Lançamentos que usam esta categoria como subcategoria.
     */
    /**
     * @return HasMany<Lancamento, $this>
     */
    public function lancamentosComoSubcategoria(): HasMany
    {
        return $this->hasMany(Lancamento::class, 'subcategoria_id');
    }

    // ─── Scopes ─────────────────────────────────────────

    /**
     * @param Builder<Categoria> $q
     * @return Builder<Categoria>
     */
    public function scopeReceitas(Builder $q): Builder
    {
        return $q->where('tipo', 'receita');
    }

    /**
     * @param Builder<Categoria> $q
     * @return Builder<Categoria>
     */
    public function scopeDespesas(Builder $q): Builder
    {
        return $q->where('tipo', 'despesa');
    }

    /**
     * @param Builder<Categoria> $q
     * @return Builder<Categoria>
     */
    public function scopeTransferencias(Builder $q): Builder
    {
        return $q->where('tipo', 'transferencia');
    }

    /**
     * @param Builder<Categoria> $q
     * @return Builder<Categoria>
     */
    public function scopeForUser(Builder $q, int $userId): Builder
    {
        return $q->where('user_id', $userId);
    }

    /**
     * Apenas categorias raiz (não são subcategorias).
     */
    /**
     * @param Builder<Categoria> $q
     * @return Builder<Categoria>
     */
    public function scopeRoots(Builder $q): Builder
    {
        return $q->whereNull('parent_id');
    }

    /**
     * Apenas subcategorias (têm parent_id).
     */
    /**
     * @param Builder<Categoria> $q
     * @return Builder<Categoria>
     */
    public function scopeChildren(Builder $q): Builder
    {
        return $q->whereNotNull('parent_id');
    }

    // ─── Helpers ────────────────────────────────────────

    /**
     * Verifica se esta categoria é uma subcategoria.
     */
    public function isSubcategoria(): bool
    {
        return $this->parent_id !== null;
    }

    /**
     * Verifica se esta categoria é uma categoria raiz (pai).
     */
    public function isRoot(): bool
    {
        return $this->parent_id === null;
    }
}
