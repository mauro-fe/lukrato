<?php

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * Model: PointsLog
 * 
 * Registro histórico de pontos ganhos/perdidos
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
