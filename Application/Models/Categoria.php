<?php

namespace Application\Models;

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
 * @method static \Illuminate\Database\Eloquent\Builder where(string $column, $operator = null, $value = null, string $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder receitas()
 * @method static \Illuminate\Database\Eloquent\Builder despesas()
 * @method static \Illuminate\Database\Eloquent\Builder forUser(int $userId)
 * @method static \Illuminate\Database\Eloquent\Builder roots()
 * @method static \Illuminate\Database\Eloquent\Builder children()
 *
 * @mixin \Illuminate\Database\Eloquent\Builder
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

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'user_id');
    }

    public function lancamentos(): HasMany
    {
        return $this->hasMany(Lancamento::class, 'categoria_id');
    }

    /**
     * Categoria pai (se esta for uma subcategoria).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Subcategorias desta categoria.
     */
    public function subcategorias(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('nome');
    }

    /**
     * Lançamentos que usam esta categoria como subcategoria.
     */
    public function lancamentosComoSubcategoria(): HasMany
    {
        return $this->hasMany(Lancamento::class, 'subcategoria_id');
    }

    // ─── Scopes ─────────────────────────────────────────

    public function scopeReceitas($q)
    {
        return $q->where('tipo', 'receita');
    }

    public function scopeDespesas($q)
    {
        return $q->where('tipo', 'despesa');
    }

    public function scopeTransferencias($q)
    {
        return $q->where('tipo', 'transferencia');
    }

    public function scopeForUser($q, int $userId)
    {
        return $q->where('user_id', $userId);
    }

    /**
     * Apenas categorias raiz (não são subcategorias).
     */
    public function scopeRoots($q)
    {
        return $q->whereNull('parent_id');
    }

    /**
     * Apenas subcategorias (têm parent_id).
     */
    public function scopeChildren($q)
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
