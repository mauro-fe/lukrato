<?php

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model OrcamentoCategoria - Orçamento mensal por categoria
 *
 * @property int $id
 * @property int $user_id
 * @property int $categoria_id
 * @property float $valor_limite
 * @property int $mes
 * @property int $ano
 * @property bool $rollover
 * @property bool $alerta_80
 * @property bool $alerta_100
 * @property bool $notificado_80
 * @property bool $notificado_100
 * @property \Carbon\Carbon|string|null $created_at
 * @property \Carbon\Carbon|string|null $updated_at
 * @property-read Categoria|null $categoria
 * @property-read Usuario|null $usuario
 *
 * @method static \Illuminate\Database\Eloquent\Builder where(string $column, $operator = null, $value = null)
 * @method static \Illuminate\Database\Eloquent\Builder forUser(int $userId)
 * @method static \Illuminate\Database\Eloquent\Builder doMes(int $mes, int $ano)
 *
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class OrcamentoCategoria extends Model
{
    protected $table = 'orcamentos_categoria';
    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'categoria_id',
        'valor_limite',
        'mes',
        'ano',
        'rollover',
        'alerta_80',
        'alerta_100',
        'notificado_80',
        'notificado_100',
    ];

    protected $casts = [
        'user_id'       => 'int',
        'categoria_id'  => 'int',
        'valor_limite'  => 'float',
        'mes'           => 'int',
        'ano'           => 'int',
        'rollover'      => 'bool',
        'alerta_80'     => 'bool',
        'alerta_100'    => 'bool',
        'notificado_80' => 'bool',
        'notificado_100' => 'bool',
    ];

    // ============================================================
    // RELATIONSHIPS
    // ============================================================

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'user_id');
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    // ============================================================
    // SCOPES
    // ============================================================

    public function scopeForUser($q, int $userId)
    {
        return $q->where('user_id', $userId);
    }

    public function scopeDoMes($q, int $mes, int $ano)
    {
        return $q->where('mes', $mes)->where('ano', $ano);
    }

    /**
     * Retorna o período formatado (ex: "02/2026")
     */
    public function getPeriodoAttribute(): string
    {
        return str_pad((string)$this->mes, 2, '0', STR_PAD_LEFT) . '/' . $this->ano;
    }
}
