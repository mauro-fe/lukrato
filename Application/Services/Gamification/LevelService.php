<?php

declare(strict_types=1);

namespace Application\Services\Gamification;

use Application\Enums\GamificationAction;
use Application\Models\PointsLog;
use Application\Models\UserProgress;
use Application\Services\Infrastructure\LogService;

class LevelService
{
    /**
     * Thresholds de pontos para cada nível.
     * FONTE DE VERDADE: Todas as outras classes devem referenciar esta constante.
     */
    public const LEVEL_THRESHOLDS = [
        1 => 0,
        2 => 300,
        3 => 500,
        4 => 700,
        5 => 1000,
        6 => 1500,
        7 => 2200,
        8 => 3000,
        9 => 4000,
        10 => 5500,
        11 => 7500,
        12 => 10000,
        13 => 15000,
        14 => 25000,
        15 => 50000,
    ];

    /**
     * Níveis que podem desbloquear conquistas de progressão.
     *
     * @var list<int>
     */
    public const ACHIEVEMENT_LEVELS = [3, 5, 8, 10, 12, 15];

    /**
     * @return array<string,mixed>
     */
    public function recalculateLevel(int $userId): array
    {
        $progress = $this->getOrCreateProgress($userId);
        $points = $progress->total_points;

        $previousLevel = $progress->current_level;
        $newLevel = $this->calculateLevelFromPoints($points);
        $levelUp = $newLevel > $previousLevel;

        $progress->current_level = $newLevel;
        $nextLevelThreshold = self::LEVEL_THRESHOLDS[$newLevel + 1] ?? null;
        $progress->points_to_next_level = $nextLevelThreshold
            ? $nextLevelThreshold - $points
            : 0;

        $progress->save();

        if ($levelUp) {
            PointsLog::create([
                'user_id' => $userId,
                'action' => GamificationAction::LEVEL_UP->value,
                'points' => 0,
                'description' => "Subiu para o nível {$newLevel}",
                'metadata' => ['new_level' => $newLevel, 'previous_level' => $previousLevel],
            ]);

            LogService::info("User {$userId} subiu para nível {$newLevel}!");
        }

        $currentThreshold = self::LEVEL_THRESHOLDS[$newLevel];
        $nextThreshold = self::LEVEL_THRESHOLDS[$newLevel + 1] ?? $currentThreshold;
        $pointsInLevel = $points - $currentThreshold;
        $pointsForLevel = $nextThreshold - $currentThreshold;

        $progressPercentage = $pointsForLevel > 0
            ? min(100, ($pointsInLevel / $pointsForLevel) * 100)
            : 100;

        return [
            'current_level' => $newLevel,
            'level_up' => $levelUp,
            'points_to_next_level' => $progress->points_to_next_level,
            'progress_percentage' => round($progressPercentage, 1),
        ];
    }

    public static function isAchievementLevel(int $level): bool
    {
        return in_array($level, self::ACHIEVEMENT_LEVELS, true);
    }

    private function getOrCreateProgress(int $userId): UserProgress
    {
        /** @var UserProgress $model */
        $model = UserProgress::firstOrCreate(
            ['user_id' => $userId],
            [
                'total_points' => 0,
                'current_level' => 1,
                'points_to_next_level' => 300,
                'current_streak' => 0,
                'best_streak' => 0,
                'last_activity_date' => null,
            ]
        );

        return $model;
    }

    private function calculateLevelFromPoints(int $points): int
    {
        foreach (array_reverse(self::LEVEL_THRESHOLDS, true) as $level => $threshold) {
            if ($points >= $threshold) {
                return $level;
            }
        }

        return 1;
    }
}
