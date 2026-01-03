<?php

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * Model: PointsLog
 *
 * Registro histórico de pontos ganhos/perdidos
 *
 * @property int $id
 * @property int $user_id
 * @property string $action
 * @property int $points
 * @property string|null $description
 * @property array|null $metadata
 * @property int|null $related_id
 * @property string|null $related_type
 * @property \Carbon\Carbon|string|null $created_at
 *
 * @method static \Illuminate\Database\Eloquent\Model|static create(array $attributes = [])
 * @method static \Illuminate\Database\Eloquent\Builder where(string $column, $operator = null, $value = null, string $boolean = 'and')
 *
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class PointsLog extends Model
{
    protected $table = 'points_log';

    protected $fillable = [
        'user_id',
        'action',
        'points',
        'description',
        'metadata',
        'related_id',
        'related_type',
    ];

    protected $casts = [
        'points' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * Usuário
     */
    public function user()
    {
        return $this->belongsTo(Usuario::class, 'user_id');
    }

    /**
     * Logs de pontos positivos
     */
    public function scopeGains($query)
    {
        return $query->where('points', '>', 0);
    }

    /**
     * Logs de pontos negativos
     */
    public function scopeLosses($query)
    {
        return $query->where('points', '<', 0);
    }

    /**
     * Filtrar por ação
     */
    public function scopeAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Logs de hoje
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', Carbon::today());
    }

    /**
     * Logs de um período
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }
}
