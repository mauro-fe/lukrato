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
        $this->requireAuthApi();

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
     * @param ?string month - Filtro opcional por mês (formato YYYY-MM)
     */
    public function getAchievements(): void
    {
        $this->requireAuthApi();

        try {
            $user = \Application\Lib\Auth::user();
            error_log("🎮 [ACHIEVEMENTS API] User ID: {$this->userId}, isPro: " . ($user->isPro() ? 'true' : 'false'));

            // Filtro por mês (opcional)
            $month = $_GET['month'] ?? null;
            error_log("🎮 [ACHIEVEMENTS API] Month filter: " . ($month ?? 'null'));

            $achievements = $this->achievementService->getUserAchievements($this->userId, $month);
            error_log("🎮 [ACHIEVEMENTS API] Total conquistas retornadas: " . count($achievements));

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

            error_log("🎮 [ACHIEVEMENTS API] Sending response - Total: {$totalCount}, Unlocked: {$unlockedCount}");

            Response::success([
                'achievements' => $achievements,
                'stats' => $stats,
            ], 'Conquistas do usuário');
        } catch (Exception $e) {
            error_log("🎮 [GAMIFICATION] Erro ao buscar conquistas: " . $e->getMessage());
            error_log("🎮 [GAMIFICATION] Stack trace: " . $e->getTraceAsString());
            Response::error('Erro ao buscar conquistas', 500);
        }
    }

    /**
     * GET /api/gamification/achievements/pending
     * Retorna conquistas desbloqueadas que ainda não foram vistas (para notificação global)
     */
    public function getPendingAchievements(): void
    {
        $this->requireAuthApi();

        try {
            $pendingAchievements = UserAchievement::with('achievement')
                ->where('user_id', $this->userId)
                ->where('notification_seen', false)
                ->get()
                ->map(function ($userAchievement) {
                    $achievement = $userAchievement->achievement;
                    return [
                        'id' => $achievement->id,
                        'name' => $achievement->name,
                        'description' => $achievement->description,
                        'icon' => $achievement->icon,
                        'points_reward' => $achievement->points_reward,
                        'unlocked_at' => $userAchievement->unlocked_at?->toDateTimeString(),
                    ];
                })
                ->toArray();

            Response::success([
                'pending' => $pendingAchievements,
                'count' => count($pendingAchievements),
            ], 'Conquistas pendentes de notificação');
        } catch (Exception $e) {
            error_log("🎮 [GAMIFICATION] Erro ao buscar conquistas pendentes: " . $e->getMessage());
            Response::error('Erro ao buscar conquistas pendentes', 500);
        }
    }

    /**
     * POST /api/gamification/achievements/mark-seen
     * Marca conquistas como vistas
     */
    public function markAchievementsSeen(): void
    {
        $this->requireAuthApi();

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
        $this->requireAuthApi();

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
        $this->requireAuthApi();

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

    /**
     * GET /api/gamification/history
     * Retorna histórico de atividades recentes (últimas ações que deram pontos)
     */
    public function getHistory(): void
    {
        $this->requireAuthApi();

        try {
            $limit = (int)($_GET['limit'] ?? 10);
            $limit = min(max($limit, 1), 50); // Entre 1 e 50

            $history = \Application\Models\PointsLog::where('user_id', $this->userId)
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($log) {
                    return [
                        'id' => $log->id,
                        'action' => $log->action,
                        'points' => $log->points,
                        'description' => $log->description ?? $this->formatActionName($log->action),
                        'created_at' => $log->created_at?->format('Y-m-d H:i:s'),
                        'relative_time' => $this->getRelativeTime($log->created_at),
                    ];
                });

            Response::success([
                'history' => $history,
                'count' => $history->count(),
            ], 'Histórico de atividades');
        } catch (Exception $e) {
            error_log("🎮 [GAMIFICATION] Erro ao buscar histórico: " . $e->getMessage());
            Response::error('Erro ao buscar histórico', 500);
        }
    }

    /**
     * Formatar nome da ação para exibição
     */
    private function formatActionName(string $action): string
    {
        $names = [
            'LAUNCH_CREATED' => 'Lançamento criado',
            'LAUNCH_EDITED' => 'Lançamento editado',
            'LAUNCH_DELETED' => 'Lançamento excluído',
            'CATEGORY_CREATED' => 'Categoria criada',
            'DAILY_LOGIN' => 'Login diário',
            'STREAK_BONUS' => 'Bônus de sequência',
            'ACHIEVEMENT_UNLOCKED' => 'Conquista desbloqueada',
            'FIRST_LAUNCH_DAY' => 'Primeiro lançamento do dia',
            'CARD_CREATED' => 'Cartão cadastrado',
            'INVOICE_PAID' => 'Fatura paga',
        ];

        return $names[$action] ?? ucwords(str_replace('_', ' ', strtolower($action)));
    }

    /**
     * Obter tempo relativo (ex: "há 2 horas")
     */
    private function getRelativeTime(?Carbon $date): string
    {
        if (!$date) return '';

        $now = Carbon::now();
        $diff = $date->diff($now);

        if ($diff->days === 0) {
            if ($diff->h === 0) {
                if ($diff->i === 0) {
                    return 'Agora';
                }
                return "Há {$diff->i} min";
            }
            return "Há {$diff->h}h";
        } elseif ($diff->days === 1) {
            return 'Ontem';
        } elseif ($diff->days < 7) {
            return "Há {$diff->days} dias";
        } else {
            return $date->format('d/m/Y');
        }
    }
}
