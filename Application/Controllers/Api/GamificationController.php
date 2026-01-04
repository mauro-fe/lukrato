<?php

namespace Application\Controllers\Api;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Services\GamificationService;
use Application\Services\AchievementService;
use Application\Services\StreakService;
use Application\Models\UserProgress;
use Application\Models\Achievement;
use Application\Models\UserAchievement;
use Application\Models\Lancamento;
use Application\Models\Categoria;
use Carbon\Carbon;
use Exception;

class GamificationController extends BaseController
{
    private GamificationService $gamificationService;
    private AchievementService $achievementService;
    private StreakService $streakService;

    public function __construct()
    {
        parent::__construct();
        $this->gamificationService = new GamificationService();
        $this->achievementService = new AchievementService();
        $this->streakService = new StreakService();
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
            $user = \Application\Lib\Auth::user();

            if (!$progress) {
                Response::success([
                    'total_points' => 0,
                    'current_level' => 1,
                    'points_to_next_level' => 300,
                    'progress_percentage' => 0,
                    'current_streak' => 0,
                    'best_streak' => 0,
                    'is_pro' => $user->isPro(),
                    'streak_protection_available' => false,
                    'streak_protection_used' => false,
                ], 'Progresso do usuário');
                return;
            }

            $streakInfo = $this->streakService->getStreakInfo($this->userId);

            Response::success([
                'total_points' => $progress->total_points,
                'current_level' => $progress->current_level,
                'points_to_next_level' => $progress->points_to_next_level,
                'progress_percentage' => $progress->progress_percentage,
                'current_streak' => $progress->current_streak,
                'best_streak' => $progress->best_streak,
                'last_activity_date' => $progress->last_activity_date?->format('Y-m-d'),
                'is_pro' => $user->isPro(),
                'streak_protection_available' => $streakInfo['protection_available'],
                'streak_protection_used' => $streakInfo['protection_used_this_month'],
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
            $user = \Application\Lib\Auth::user();
            $achievements = $this->achievementService->getUserAchievements($this->userId);

            // Estatísticas gerais
            $totalCount = count($achievements);
            $unlockedCount = count(array_filter($achievements, fn($a) => $a['unlocked']));

            $stats = [
                'total_achievements' => $totalCount,
                'unlocked_count' => $unlockedCount,
                'completion_percentage' => $totalCount > 0
                    ? round(($unlockedCount / $totalCount) * 100, 1)
                    : 0,
                'is_pro' => $user->isPro(),
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

    /**
     * GET /api/gamification/stats
     * Retorna estatísticas gerais para o dashboard
     */
    public function getStats(): void
    {
        $this->requireAuth();

        try {
            $user = \Application\Lib\Auth::user();

            // Total de lançamentos
            $totalLancamentos = Lancamento::where('user_id', $this->userId)->count();

            // Total de categorias
            $totalCategorias = Categoria::where('user_id', $this->userId)->count();

            // Meses ativos (meses com lançamentos)
            $mesesAtivos = Lancamento::where('user_id', $this->userId)
                ->selectRaw('COUNT(DISTINCT DATE_FORMAT(data, "%Y-%m")) as total')
                ->first()
                ->total ?? 0;

            // Progresso
            $progress = UserProgress::where('user_id', $this->userId)->first();

            Response::success([
                'total_lancamentos' => $totalLancamentos,
                'total_categorias' => $totalCategorias,
                'meses_ativos' => $mesesAtivos,
                'pontos_total' => $progress?->total_points ?? 0,
                'nivel_atual' => $progress?->current_level ?? 1,
                'streak_atual' => $progress?->current_streak ?? 0,
                'is_pro' => $user->isPro(),
            ], 'Estatísticas do usuário');
        } catch (Exception $e) {
            error_log("🎮 [GAMIFICATION] Erro ao buscar estatísticas: " . $e->getMessage());
            Response::error('Erro ao buscar estatísticas', 500);
        }
    }
}
