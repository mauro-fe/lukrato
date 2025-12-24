<?php

namespace Application\Services;

use Application\Models\UserProgress;
use Application\Models\Achievement;
use Application\Models\UserAchievement;
use Application\Models\PointsLog;
use Application\Models\Lancamento;
use Application\Models\Categoria;
use Application\Enums\GamificationAction;
use Application\Enums\AchievementType;
use Carbon\Carbon;
use Exception;

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
     */
    private const LEVEL_THRESHOLDS = [
        1 => 0,
        2 => 100,
        3 => 250,
        4 => 500,
        5 => 1000,
    ];

    /**
     * Adicionar pontos ao usuário por uma ação
     * 
     * @param int $userId
     * @param GamificationAction $action
     * @param int|null $relatedId ID do registro relacionado (opcional)
     * @param string|null $relatedType Tipo do registro (opcional)
     * @param array $metadata Metadados adicionais (opcional)
     * @return array Resultado com pontos ganhos e novos dados
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
                error_log("🎮 [GAMIFICATION] Ação duplicada ignorada: {$action->value} para user {$userId}");
                return [
                    'success' => false,
                    'message' => 'Pontos já registrados para esta ação',
                ];
            }

            $points = $action->points();

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
                'metadata' => $metadata,
                'related_id' => $relatedId,
                'related_type' => $relatedType,
            ]);

            // Atualizar total de pontos
            $progress->total_points += $points;
            $progress->save();

            // Recalcular nível
            $levelData = $this->recalculateLevel($userId);

            // Verificar conquistas
            $newAchievements = $this->checkAchievements($userId);

            error_log("🎮 [GAMIFICATION] +{$points} pontos para user {$userId} - Ação: {$action->value}");

            return [
                'success' => true,
                'points_gained' => $points,
                'total_points' => $progress->total_points,
                'level' => $levelData['current_level'],
                'level_up' => $levelData['level_up'],
                'progress_percentage' => $levelData['progress_percentage'],
                'new_achievements' => $newAchievements,
            ];
        } catch (Exception $e) {
            error_log("🎮 [GAMIFICATION] ERRO ao adicionar pontos: " . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Erro ao processar pontos',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Atualizar streak do usuário
     * 
     * @param int $userId
     * @return array Dados da streak atualizada
     */
    public function updateStreak(int $userId): array
    {
        try {
            $progress = $this->getOrCreateProgress($userId);
            $today = Carbon::today();
            $lastActivity = $progress->last_activity_date
                ? Carbon::parse($progress->last_activity_date)
                : null;

            // Se já registrou hoje, não faz nada
            if ($lastActivity && $lastActivity->isSameDay($today)) {
                return [
                    'streak' => $progress->current_streak,
                    'already_counted' => true,
                ];
            }

            // Se foi ontem, incrementa streak
            if ($lastActivity && $lastActivity->isSameDay($today->copy()->subDay())) {
                $progress->current_streak += 1;

                // Atualizar melhor streak
                if ($progress->current_streak > $progress->best_streak) {
                    $progress->best_streak = $progress->current_streak;
                }

                // Verificar marcos de streak (7 e 30 dias)
                if ($progress->current_streak === 7) {
                    $this->addPoints($userId, GamificationAction::STREAK_7_DAYS);
                } elseif ($progress->current_streak === 30) {
                    $this->addPoints($userId, GamificationAction::STREAK_30_DAYS);
                }
            } else {
                // Se pulou dias, zera streak
                if ($progress->current_streak > 0) {
                    error_log("🎮 [GAMIFICATION] Streak zerada para user {$userId} - dias sem atividade");
                }
                $progress->current_streak = 1;
            }

            $progress->last_activity_date = $today;
            $progress->save();

            // Dar pontos por atividade diária
            $dailyPoints = $this->addPoints($userId, GamificationAction::DAILY_ACTIVITY);

            return [
                'streak' => $progress->current_streak,
                'best_streak' => $progress->best_streak,
                'daily_points_gained' => $dailyPoints['success'] ?? false,
            ];
        } catch (Exception $e) {
            error_log("🎮 [GAMIFICATION] ERRO ao atualizar streak: " . $e->getMessage());
            return ['streak' => 0, 'error' => $e->getMessage()];
        }
    }

    /**
     * Recalcular nível do usuário baseado nos pontos totais
     * 
     * @param int $userId
     * @return array Dados do nível
     */
    public function recalculateLevel(int $userId): array
    {
        $progress = $this->getOrCreateProgress($userId);
        $points = $progress->total_points;

        $previousLevel = $progress->current_level;
        $newLevel = $this->calculateLevelFromPoints($points);

        $levelUp = $newLevel > $previousLevel;

        if ($levelUp) {
            $progress->current_level = $newLevel;

            // Calcular pontos para próximo nível
            $nextLevelThreshold = self::LEVEL_THRESHOLDS[$newLevel + 1] ?? null;
            $progress->points_to_next_level = $nextLevelThreshold
                ? $nextLevelThreshold - $points
                : 0;

            $progress->save();

            // Registrar level up
            PointsLog::create([
                'user_id' => $userId,
                'action' => GamificationAction::LEVEL_UP->value,
                'points' => 0,
                'description' => "Subiu para o nível {$newLevel}",
                'metadata' => ['new_level' => $newLevel, 'previous_level' => $previousLevel],
            ]);

            error_log("🎮 [GAMIFICATION] User {$userId} subiu para nível {$newLevel}!");

            // Verificar conquista de nível 5
            if ($newLevel === 5) {
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
     * 
     * @param int $userId
     * @return array Lista de novas conquistas desbloqueadas
     */
    public function checkAchievements(int $userId): array
    {
        $newAchievements = [];

        try {
            // Buscar conquistas ativas que o usuário ainda não tem
            $unlockedIds = UserAchievement::where('user_id', $userId)
                ->pluck('achievement_id')
                ->toArray();

            $availableAchievements = Achievement::active()
                ->whereNotIn('id', $unlockedIds)
                ->get();

            foreach ($availableAchievements as $achievement) {
                if ($this->checkAchievementCondition($userId, $achievement->code)) {
                    $unlocked = $this->unlockAchievement($userId, $achievement->id);
                    if ($unlocked) {
                        $newAchievements[] = [
                            'id' => $achievement->id,
                            'code' => $achievement->code,
                            'name' => $achievement->name,
                            'description' => $achievement->description,
                            'icon' => $achievement->icon,
                            'points_reward' => $achievement->points_reward,
                        ];
                    }
                }
            }
        } catch (Exception $e) {
            error_log("🎮 [GAMIFICATION] ERRO ao verificar conquistas: " . $e->getMessage());
        }

        return $newAchievements;
    }

    /**
     * Desbloquear conquista para usuário
     * 
     * @param int $userId
     * @param int $achievementId
     * @return bool
     */
    public function unlockAchievement(int $userId, int $achievementId): bool
    {
        try {
            // Verificar se já tem
            $exists = UserAchievement::where('user_id', $userId)
                ->where('achievement_id', $achievementId)
                ->exists();

            if ($exists) {
                return false;
            }

            $achievement = Achievement::find($achievementId);
            if (!$achievement) {
                return false;
            }

            // Criar registro de conquista
            UserAchievement::create([
                'user_id' => $userId,
                'achievement_id' => $achievementId,
                'unlocked_at' => now(),
                'notification_seen' => false,
            ]);

            // Adicionar pontos de recompensa
            if ($achievement->points_reward > 0) {
                $progress = $this->getOrCreateProgress($userId);
                $progress->total_points += $achievement->points_reward;
                $progress->save();

                // Registrar no log
                PointsLog::create([
                    'user_id' => $userId,
                    'action' => 'achievement_unlock',
                    'points' => $achievement->points_reward,
                    'description' => "Conquista desbloqueada: {$achievement->name}",
                    'metadata' => ['achievement_code' => $achievement->code],
                    'related_id' => $achievementId,
                    'related_type' => 'achievement',
                ]);

                // Recalcular nível
                $this->recalculateLevel($userId);
            }

            error_log("🎮 [GAMIFICATION] User {$userId} desbloqueou: {$achievement->name} (+{$achievement->points_reward} pontos)");

            return true;
        } catch (Exception $e) {
            error_log("🎮 [GAMIFICATION] ERRO ao desbloquear conquista: " . $e->getMessage());
            return false;
        }
    }

    // ========================================================================
    // MÉTODOS PRIVADOS / HELPERS
    // ========================================================================

    /**
     * Obter ou criar progresso do usuário
     */
    private function getOrCreateProgress(int $userId): UserProgress
    {
        return UserProgress::firstOrCreate(
            ['user_id' => $userId],
            [
                'total_points' => 0,
                'current_level' => 1,
                'points_to_next_level' => 100,
                'current_streak' => 0,
                'best_streak' => 0,
                'last_activity_date' => null,
            ]
        );
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

    /**
     * Verificar condição específica de uma conquista
     */
    private function checkAchievementCondition(int $userId, string $code): bool
    {
        try {
            $achievementType = AchievementType::from($code);
        } catch (\ValueError $e) {
            return false;
        }

        $progress = $this->getOrCreateProgress($userId);

        return match ($achievementType) {
            AchievementType::FIRST_LAUNCH => $this->hasLaunches($userId, 1),
            AchievementType::STREAK_7 => $progress->current_streak >= 7,
            AchievementType::STREAK_30 => $progress->current_streak >= 30,
            AchievementType::LEVEL_5 => $progress->current_level >= 5,
            AchievementType::TOTAL_100_LAUNCHES => $this->hasLaunches($userId, 100),
            AchievementType::TOTAL_10_CATEGORIES => $this->hasCategories($userId, 10),
            AchievementType::POSITIVE_MONTH => $this->hasPositiveMonth($userId),
            AchievementType::BALANCE_POSITIVE => $this->hasPositiveBalance($userId),
        };
    }

    /**
     * Verificar se usuário tem X lançamentos
     */
    private function hasLaunches(int $userId, int $count): bool
    {
        return Lancamento::where('user_id', $userId)->count() >= $count;
    }

    /**
     * Verificar se usuário tem X categorias
     */
    private function hasCategories(int $userId, int $count): bool
    {
        return Categoria::where('user_id', $userId)->count() >= $count;
    }

    /**
     * Verificar se teve mês com saldo positivo
     */
    private function hasPositiveMonth(int $userId): bool
    {
        // Implementação simplificada - verificar último mês fechado
        $lastMonth = Carbon::now()->subMonth();

        $receitas = Lancamento::where('user_id', $userId)
            ->where('tipo', 'receita')
            ->whereYear('data', $lastMonth->year)
            ->whereMonth('data', $lastMonth->month)
            ->sum('valor');

        $despesas = Lancamento::where('user_id', $userId)
            ->where('tipo', 'despesa')
            ->whereYear('data', $lastMonth->year)
            ->whereMonth('data', $lastMonth->month)
            ->sum('valor');

        return ($receitas - $despesas) > 0;
    }

    /**
     * Verificar se saldo geral é positivo
     */
    private function hasPositiveBalance(int $userId): bool
    {
        $receitas = Lancamento::where('user_id', $userId)
            ->where('tipo', 'receita')
            ->sum('valor');

        $despesas = Lancamento::where('user_id', $userId)
            ->where('tipo', 'despesa')
            ->sum('valor');

        return ($receitas - $despesas) > 0;
    }
}
