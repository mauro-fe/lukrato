<?php

declare(strict_types=1);

namespace Application\Services\Gamification;

use Application\Models\UserProgress;
use Application\Models\Achievement;
use Application\Models\UserAchievement;
use Application\Models\PointsLog;
use Application\Models\Lancamento;
use Application\Models\Categoria;
use Application\Enums\GamificationAction;
use Application\Enums\AchievementType;
use Application\Enums\LogCategory;
use Carbon\Carbon;
use Exception;
use Application\Services\Infrastructure\LogService;

/**
 * Service: GamificationService
 * 
 * Gerencia todo o sistema de gamificação:
 * - Pontos
 * - Streaks
 * - Níveis
 * - Conquistas
 */
class GamificationService
{
    /**
     * Thresholds de pontos para cada nível
     * Níveis expandidos de 1 a 15
     * FONTE DE VERDADE: Todas as outras classes devem referenciar esta constante
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
     * Adicionar pontos ao usuário por uma ação
     * 
     * @param int $userId
     * @param GamificationAction $action
     * @param int|null $relatedId ID do registro relacionado (opcional)
     * @param string|null $relatedType Tipo do registro (opcional)
     * @param array $metadata Metadados adicionais (opcional)
     * @return array<string,mixed> Resultado com pontos ganhos e novos dados
     */
    public function addPoints(
        int $userId,
        GamificationAction $action,
        ?int $relatedId = null,
        ?string $relatedType = null,
        array $metadata = []
    ): array {
        try {
            // Verificar se já não foi registrado (evitar duplicação)
            if ($this->isDuplicateAction($userId, $action, $relatedId, $relatedType)) {
                LogService::info("Ação duplicada ignorada: {$action->value} para user {$userId}");
                return [
                    'success' => false,
                    'message' => 'Pontos já registrados para esta ação',
                ];
            }

            // Obter plano do usuário
            $user = \Application\Models\Usuario::find($userId);
            $isPro = $user ? $user->isPro() : false;

            // Calcular pontos com base no plano
            // Pro já recebe pontos maiores via GamificationAction::points($isPro)
            $points = $action->points($isPro);

            // Se é ação diária, verificar se já foi feita hoje
            if ($action->isOncePerDay()) {
                $alreadyDoneToday = PointsLog::where('user_id', $userId)
                    ->where('action', $action->value)
                    ->whereDate('created_at', Carbon::today())
                    ->exists();

                if ($alreadyDoneToday) {
                    return [
                        'success' => false,
                        'message' => 'Pontos diários já concedidos',
                    ];
                }
            }

            // Obter ou criar progresso do usuário
            $progress = $this->getOrCreateProgress($userId);

            // Registrar no log de pontos
            PointsLog::create([
                'user_id' => $userId,
                'action' => $action->value,
                'points' => $points,
                'description' => $action->description(),
                'metadata' => array_merge($metadata, [
                    'is_pro' => $isPro,
                    'base_points' => $points,
                ]),
                'related_id' => $relatedId,
                'related_type' => $relatedType,
            ]);

            // Atualizar total de pontos
            $progress->total_points += $points;
            $progress->save();

            // Recalcular nível
            $levelData = $this->recalculateLevel($userId);

            // Verificar conquistas
            $achievementService = new AchievementService();
            $newAchievements = $achievementService->checkAndUnlockAchievements($userId, $action->value);

            LogService::info("+{$points} pontos para user {$userId} - Ação: {$action->value}" . ($isPro ? ' [PRO]' : ''));

            return [
                'success' => true,
                'points_gained' => $points,
                'base_points' => $points,
                'is_pro' => $isPro,
                'total_points' => $progress->total_points,
                'level' => $levelData['current_level'],
                'level_up' => $levelData['level_up'],
                'progress_percentage' => $levelData['progress_percentage'],
                'new_achievements' => $newAchievements,
            ];
        } catch (Exception $e) {
            LogService::captureException($e, LogCategory::GAMIFICATION, [
                'action' => $action->value,
                'user_id' => $userId,
                'related_id' => $relatedId,
            ]);

            return [
                'success' => false,
                'message' => 'Erro ao processar pontos',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Atualizar streak do usuário
     * Delega para o StreakService
     * 
     * @param int $userId
     * @return array<string,mixed> Dados da streak atualizada
     */
    public function updateStreak(int $userId): array
    {
        $streakService = new StreakService();
        return $streakService->updateStreak($userId);
    }

    /**
     * Recalcular nível do usuário baseado nos pontos totais
     * 
     * @param int $userId
     * @return array<string,mixed> Dados do nível
     */
    public function recalculateLevel(int $userId): array
    {
        $progress = $this->getOrCreateProgress($userId);
        $points = $progress->total_points;

        $previousLevel = $progress->current_level;
        $newLevel = $this->calculateLevelFromPoints($points);

        $levelUp = $newLevel > $previousLevel;

        // Atualizar nível e calcular pontos para próximo nível
        $progress->current_level = $newLevel;
        $nextLevelThreshold = self::LEVEL_THRESHOLDS[$newLevel + 1] ?? null;
        $progress->points_to_next_level = $nextLevelThreshold
            ? $nextLevelThreshold - $points
            : 0;

        $progress->save();

        if ($levelUp) {
            // Registrar level up
            PointsLog::create([
                'user_id' => $userId,
                'action' => GamificationAction::LEVEL_UP->value,
                'points' => 0,
                'description' => "Subiu para o nível {$newLevel}",
                'metadata' => ['new_level' => $newLevel, 'previous_level' => $previousLevel],
            ]);

            LogService::info("User {$userId} subiu para nível {$newLevel}!");

            // Verificar conquistas de nível (3, 5, 8, 10, 12, 15)
            if (in_array($newLevel, [3, 5, 8, 10, 12, 15])) {
                $this->checkAchievements($userId);
            }
        }

        // Calcular progresso percentual
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

    /**
     * Verificar e desbloquear conquistas
     * @deprecated Use AchievementService->checkAndUnlockAchievements()
     * 
     * @param int $userId
     * @return array<int,array<string,mixed>> Lista de novas conquistas desbloqueadas
     */
    public function checkAchievements(int $userId): array
    {
        $achievementService = new AchievementService();
        $achievements = $achievementService->checkAndUnlockAchievements($userId);

        // Formatar para compatibilidade
        return array_map(function ($ach) {
            return [
                'id' => $ach['id'],
                'code' => $ach['code'],
                'name' => $ach['name'],
                'description' => $ach['description'],
                'icon' => $ach['icon'],
                'points_reward' => $ach['points_reward'],
            ];
        }, $achievements);
    }

    // ========================================================================
    // MÉTODOS PRIVADOS / HELPERS
    // ========================================================================

    /**
     * Obter ou criar progresso do usuário
     */
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

    /**
     * Verificar se ação já foi registrada (evitar duplicação)
     */
    private function isDuplicateAction(
        int $userId,
        GamificationAction $action,
        ?int $relatedId,
        ?string $relatedType
    ): bool {
        // Se não tem ID relacionado, não é duplicata
        if (!$relatedId || !$relatedType) {
            return false;
        }

        // Verificar se já existe log para este registro específico
        return PointsLog::where('user_id', $userId)
            ->where('action', $action->value)
            ->where('related_id', $relatedId)
            ->where('related_type', $relatedType)
            ->exists();
    }

    /**
     * Calcular nível baseado em pontos
     */
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
