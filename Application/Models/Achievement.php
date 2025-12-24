<?php

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model: Achievement (Conquista)
 * 
 * Representa uma conquista disponível no sistema
 */
class Achievement extends Model
{
    protected $table = 'achievements';

    protected $fillable = [
        'code',
        'name',
        'description',
        'icon',
        'points_reward',
        'category',
        'active',
    ];

    protected $casts = [
        'points_reward' => 'integer',
        'active' => 'boolean',
    ];

    /**
     * Usuários que desbloquearam esta conquista
     */
    public function users()
    {
        return $this->belongsToMany(
            Usuario::class,
            'user_achievements',
            'achievement_id',
            'user_id'
        )->withTimestamps()
            ->withPivot('unlocked_at', 'notification_seen');
    }

    /**
     * Registros de desbloqueio
     */
    public function unlocks()
    {
        return $this->hasMany(UserAchievement::class, 'achievement_id');
    }

    /**
     * Verificar se está ativa
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Filtrar por categoria
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}
