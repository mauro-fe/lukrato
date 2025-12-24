<?php

namespace Application\Controllers\Api;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Services\GamificationService;
use Application\Models\UserProgress;
use Application\Models\Achievement;
use Application\Models\UserAchievement;
use Exception;

class GamificationController extends BaseController
{
    private GamificationService $gamificationService;

    public function __construct()
    {
        parent::__construct();
        $this->gamificationService = new GamificationService();
    }

    /**
     * GET /api/gamification/progress
     * Retorna o progresso completo do usuário
     */
    public function getProgress(): void
    {
        $this->requireAuth();

        try {
            $progress = UserProgress::where('user_id', $this->userId)->first();

            if (!$progress) {
                Response::success([
                    'total_points' => 0,
                    'current_level' => 1,
                    'points_to_next_level' => 100,
                    'progress_percentage' => 0,
                    'current_streak' => 0,
                    'best_streak' => 0,
                ], 'Progresso do usuário');
                return;
            }

            Response::success([
                'total_points' => $progress->total_points,
                'current_level' => $progress->current_level,
                'points_to_next_level' => $progress->points_to_next_level,
                'progress_percentage' => $progress->progress_percentage,
                'current_streak' => $progress->current_streak,
                'best_streak' => $progress->best_streak,
                'last_activity_date' => $progress->last_activity_date?->format('Y-m-d'),
            ], 'Progresso do usuário');
        } catch (Exception $e) {
            error_log("🎮 [GAMIFICATION] Erro ao buscar progresso: " . $e->getMessage());
            Response::error('Erro ao buscar progresso', 500);
        }
    }

    /**
     * GET /api/gamification/achievements
     * Retorna conquistas disponíveis e desbloqueadas
     */
    public function getAchievements(): void
    {
        $this->requireAuth();

        try {
            // Buscar todas as conquistas ativas
            $allAchievements = Achievement::active()->orderBy('category')->orderBy('points_reward')->get();

            // Buscar conquistas desbloqueadas pelo usuário
            $unlockedIds = UserAchievement::where('user_id', $this->userId)
                ->pluck('achievement_id')
                ->toArray();

            // Montar resposta com status de cada conquista
            $achievements = $allAchievements->map(function ($achievement) use ($unlockedIds) {
                $userAchievement = UserAchievement::where('user_id', $this->userId)
                    ->where('achievement_id', $achievement->id)
                    ->first();

                return [
                    'id' => $achievement->id,
                    'code' => $achievement->code,
                    'name' => $achievement->name,
                    'description' => $achievement->description,
                    'icon' => $achievement->icon,
                    'points_reward' => $achievement->points_reward,
                    'category' => $achievement->category,
                    'unlocked' => in_array($achievement->id, $unlockedIds),
                    'unlocked_at' => $userAchievement?->unlocked_at?->format('Y-m-d H:i:s'),
                    'notification_seen' => $userAchievement?->notification_seen ?? false,
                ];
            });

            // Estatísticas gerais
            $stats = [
                'total_achievements' => $allAchievements->count(),
                'unlocked_count' => count($unlockedIds),
                'completion_percentage' => $allAchievements->count() > 0
                    ? round((count($unlockedIds) / $allAchievements->count()) * 100, 1)
                    : 0,
            ];

            Response::success([
                'achievements' => $achievements,
                'stats' => $stats,
            ], 'Conquistas do usuário');
        } catch (Exception $e) {
            error_log("🎮 [GAMIFICATION] Erro ao buscar conquistas: " . $e->getMessage());
            Response::error('Erro ao buscar conquistas', 500);
        }
    }

    /**
     * POST /api/gamification/achievements/mark-seen
     * Marca conquistas como vistas
     */
    public function markAchievementsSeen(): void
    {
        $this->requireAuth();

        try {
            $payload = $this->getRequestPayload();
            $achievementIds = $payload['achievement_ids'] ?? [];

            if (empty($achievementIds) || !is_array($achievementIds)) {
                Response::error('IDs de conquistas inválidos', 400);
                return;
            }

            $updated = UserAchievement::where('user_id', $this->userId)
                ->whereIn('achievement_id', $achievementIds)
                ->update(['notification_seen' => true]);

            Response::success([
                'marked_count' => $updated,
            ], 'Conquistas marcadas como vistas');
        } catch (Exception $e) {
            error_log("🎮 [GAMIFICATION] Erro ao marcar conquistas: " . $e->getMessage());
            Response::error('Erro ao marcar conquistas', 500);
        }
    }

    /**
     * GET /api/gamification/leaderboard
     * Retorna ranking dos top usuários (top 10)
     */
    public function getLeaderboard(): void
    {
        $this->requireAuth();

        try {
            $topUsers = UserProgress::with('user:id,nome')
                ->orderBy('total_points', 'desc')
                ->orderBy('current_level', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($progress, $index) {
                    return [
                        'position' => $index + 1,
                        'user_id' => $progress->user_id,
                        'user_name' => $progress->user->nome ?? 'Usuário',
                        'total_points' => $progress->total_points,
                        'current_level' => $progress->current_level,
                        'best_streak' => $progress->best_streak,
                    ];
                });

            // Posição do usuário atual
            $userProgress = UserProgress::where('user_id', $this->userId)->first();
            $userPosition = null;

            if ($userProgress) {
                $userPosition = UserProgress::where('total_points', '>', $userProgress->total_points)
                    ->orWhere(function ($query) use ($userProgress) {
                        $query->where('total_points', '=', $userProgress->total_points)
                            ->where('current_level', '>', $userProgress->current_level);
                    })
                    ->count() + 1;
            }

            Response::success([
                'leaderboard' => $topUsers,
                'user_position' => $userPosition,
            ], 'Ranking de usuários');
        } catch (Exception $e) {
            error_log("🎮 [GAMIFICATION] Erro ao buscar leaderboard: " . $e->getMessage());
            Response::error('Erro ao buscar ranking', 500);
        }
    }
}
