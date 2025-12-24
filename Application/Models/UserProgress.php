<?php

namespace Application\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model: UserProgress
 * 
 * Representa o progresso de gamificação de um usuário
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
    ];

    protected $casts = [
        'total_points' => 'integer',
        'current_level' => 'integer',
        'points_to_next_level' => 'integer',
        'current_streak' => 'integer',
        'best_streak' => 'integer',
        'last_activity_date' => 'date',
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
        $thresholds = [0, 100, 250, 500, 1000];
        $index = max(0, $this->current_level - 2);
        return $thresholds[$index] ?? 0;
    }

    /**
     * Threshold de pontos do nível atual
     */
    private function getCurrentLevelThreshold(): int
    {
        $thresholds = [0, 100, 250, 500, 1000];
        $index = max(0, $this->current_level - 1);
        return $thresholds[$index] ?? 1000;
    }
}
