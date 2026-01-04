<?php

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model: Achievement (Conquista)
 *
 * Representa uma conquista disponível no sistema
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property string|null $icon
 * @property int $points_reward
 * @property string|null $category
 * @property bool $active
 *
 * @method static \Illuminate\Database\Eloquent\Builder active()
 * @method static \Illuminate\Database\Eloquent\Builder where(string $column, $operator = null, $value = null, string $boolean = 'and')
 *
 * @mixin \Illuminate\Database\Eloquent\Builder
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
        'plan_type',
        'sort_order',
        'active',
    ];

    protected $casts = [
        'points_reward' => 'integer',
        'sort_order' => 'integer',
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

    /**
     * Filtrar por tipo de plano
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $planType 'free', 'pro', 'all'
     */
    public function scopePlanType($query, string $planType)
    {
        if ($planType === 'all') {
            return $query;
        }
        return $query->where(function ($q) use ($planType) {
            $q->where('plan_type', $planType)
                ->orWhere('plan_type', 'all');
        });
    }

    /**
     * Verificar se conquista é exclusiva Pro
     */
    public function isProOnly(): bool
    {
        return $this->plan_type === 'pro';
    }
}
