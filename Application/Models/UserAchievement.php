<?php

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model: UserAchievement
 * 
 * Relacionamento entre usuário e conquista desbloqueada
 */
class UserAchievement extends Model
{
    protected $table = 'user_achievements';

    protected $fillable = [
        'user_id',
        'achievement_id',
        'unlocked_at',
        'notification_seen',
    ];

    protected $casts = [
        'unlocked_at' => 'datetime',
        'notification_seen' => 'boolean',
    ];

    /**
     * Usuário
     */
    public function user()
    {
        return $this->belongsTo(Usuario::class, 'user_id');
    }

    /**
     * Conquista
     */
    public function achievement()
    {
        return $this->belongsTo(Achievement::class, 'achievement_id');
    }

    /**
     * Marcar notificação como vista
     */
    public function markAsSeen(): bool
    {
        $this->notification_seen = true;
        return $this->save();
    }

    /**
     * Conquistas não vistas
     */
    public function scopeUnseen($query)
    {
        return $query->where('notification_seen', false);
    }

    /**
     * Conquistas recentes (últimos 7 dias)
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('unlocked_at', '>=', now()->subDays($days));
    }
}
