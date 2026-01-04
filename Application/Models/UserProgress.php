<?php

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model: UserProgress
 *
 * Representa o progresso de gamificação de um usuário
 *
 * @property int $id
 * @property int $user_id
 * @property int $total_points
 * @property int $current_level
 * @property int $points_to_next_level
 * @property int $current_streak
 * @property int $best_streak
 * @property \Carbon\Carbon|string|null $last_activity_date
 *
 * @method static \Illuminate\Database\Eloquent\Builder where(string $column, $operator = null, $value = null, string $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Model|null find(int|string $id)
 * @method static \Illuminate\Database\Eloquent\Model|static create(array $attributes = [])
 * @method static \Illuminate\Database\Eloquent\Model firstOrCreate(array $attributes = [], array $values = [])
 *
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class UserProgress extends Model
{
    protected $table = 'user_progress';

    protected $fillable = [
        'user_id',
        'total_points',
        'current_level',
        'points_to_next_level',
        'current_streak',
        'best_streak',
        'last_activity_date',
        'streak_freeze_used_this_month',
        'streak_freeze_date',
        'streak_freeze_month',
    ];

    protected $casts = [
        'total_points' => 'integer',
        'current_level' => 'integer',
        'points_to_next_level' => 'integer',
        'current_streak' => 'integer',
        'best_streak' => 'integer',
        'last_activity_date' => 'date',
        'streak_freeze_used_this_month' => 'boolean',
        'streak_freeze_date' => 'date',
    ];

    /**
     * Usuário dono do progresso
     */
    public function user()
    {
        return $this->belongsTo(Usuario::class, 'user_id');
    }

    /**
     * Histórico de pontos
     */
    public function pointsHistory()
    {
        return $this->hasMany(PointsLog::class, 'user_id', 'user_id')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Conquistas desbloqueadas
     */
    public function achievements()
    {
        return $this->hasMany(UserAchievement::class, 'user_id', 'user_id');
    }

    /**
     * Calcular progresso percentual para próximo nível
     */
    public function getProgressPercentageAttribute(): float
    {
        if ($this->points_to_next_level <= 0) {
            return 100.0;
        }

        // Pontos necessários desde o nível anterior
        $previousLevelThreshold = $this->getPreviousLevelThreshold();
        $currentLevelThreshold = $this->getCurrentLevelThreshold();
        $pointsInCurrentLevel = $this->total_points - $previousLevelThreshold;
        $pointsNeededForLevel = $currentLevelThreshold - $previousLevelThreshold;

        if ($pointsNeededForLevel <= 0) {
            return 100.0;
        }

        $percentage = ($pointsInCurrentLevel / $pointsNeededForLevel) * 100;
        return min(100, max(0, $percentage));
    }

    /**
     * Threshold de pontos do nível anterior
     */
    private function getPreviousLevelThreshold(): int
    {
        // Níveis: 1→0, 2→300, 3→500, 4→700, 5→1000, 6→1500, 7→2200, 8→3000
        $thresholds = [0, 300, 500, 700, 1000, 1500, 2200, 3000];
        $index = max(0, $this->current_level - 2);
        return $thresholds[$index] ?? 0;
    }

    /**
     * Threshold de pontos do nível atual
     */
    private function getCurrentLevelThreshold(): int
    {
        // Níveis: 1→0, 2→300, 3→500, 4→700, 5→1000, 6→1500, 7→2200, 8→3000
        $thresholds = [0, 300, 500, 700, 1000, 1500, 2200, 3000];
        $index = max(0, $this->current_level - 1);
        return $thresholds[$index] ?? 3000;
    }
}
