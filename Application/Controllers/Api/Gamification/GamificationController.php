<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Gamification;

use Application\Controllers\ApiController;
use Application\Core\Response;
use Application\Models\Categoria;
use Application\Models\Lancamento;
use Application\Models\UserAchievement;
use Application\Models\UserProgress;
use Application\Services\Gamification\AchievementService;
use Application\Services\Gamification\GamificationService;
use Application\Services\Gamification\MissionService;
use Application\Services\Gamification\StreakService;
use Carbon\Carbon;
use Throwable;

class GamificationController extends ApiController
{
    private GamificationService $gamificationService;
    private AchievementService $achievementService;
    private StreakService $streakService;
    private MissionService $missionService;

    public function __construct(
        ?GamificationService $gamificationService = null,
        ?AchievementService $achievementService = null,
        ?StreakService $streakService = null,
        ?MissionService $missionService = null
    ) {
        parent::__construct();
        $this->gamificationService = $gamificationService ?? new GamificationService();
        $this->achievementService = $achievementService ?? new AchievementService();
        $this->streakService = $streakService ?? new StreakService();
        $this->missionService = $missionService ?? new MissionService();
    }

    /**
     * GET /api/gamification/progress
     * Retorna o progresso completo do usuário.
     * Também verifica e desbloqueia conquistas pendentes (perfil completo, etc).
     */
    public function getProgress(): Response
    {
        $user = $this->requireApiUserAndReleaseSessionOrFail();

        try {
            $progress = UserProgress::where('user_id', $this->userId)->first();

            // Verificar conquistas que não dependem de lançamentos.
            $this->achievementService->checkAndUnlockAchievements($this->userId, 'dashboard_load');

            if (!$progress) {
                return Response::successResponse([
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
            }

            $streakInfo = $this->streakService->getStreakInfo($this->userId);

            return Response::successResponse([
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
                'level_thresholds' => GamificationService::LEVEL_THRESHOLDS,
            ], 'Progresso do usuário');
        } catch (Throwable $e) {
            \Application\Services\Infrastructure\LogService::safeErrorLog('[GAMIFICATION] Erro ao buscar progresso: ' . $e->getMessage());

            return Response::errorResponse('Erro ao buscar progresso', 500);
        }
    }

    /**
     * GET /api/gamification/achievements
     * Retorna conquistas disponíveis e desbloqueadas.
     */
    public function getAchievements(): Response
    {
        $user = $this->requireApiUserAndReleaseSessionOrFail();

        try {
            \Application\Services\Infrastructure\LogService::safeErrorLog('[ACHIEVEMENTS API] User ID: ' . $this->userId . ', isPro: ' . ($user->isPro() ? 'true' : 'false'));

            $month = $this->getQuery('month');
            \Application\Services\Infrastructure\LogService::safeErrorLog('[ACHIEVEMENTS API] Month filter: ' . ($month ?? 'null'));

            $achievements = $this->achievementService->getUserAchievements($this->userId, $month);
            \Application\Services\Infrastructure\LogService::safeErrorLog('[ACHIEVEMENTS API] Total conquistas retornadas: ' . count($achievements));

            $totalCount = count($achievements);
            $unlockedCount = count(array_filter($achievements, static fn(array $achievement): bool => (bool) ($achievement['unlocked'] ?? false)));

            $stats = [
                'total_achievements' => $totalCount,
                'unlocked_count' => $unlockedCount,
                'completion_percentage' => $totalCount > 0
                    ? round(($unlockedCount / $totalCount) * 100, 1)
                    : 0,
                'is_pro' => $user->isPro(),
            ];

            \Application\Services\Infrastructure\LogService::safeErrorLog('[ACHIEVEMENTS API] Sending response - Total: ' . $totalCount . ', Unlocked: ' . $unlockedCount);

            return Response::successResponse([
                'achievements' => $achievements,
                'stats' => $stats,
            ], 'Conquistas do usuário');
        } catch (Throwable $e) {
            \Application\Services\Infrastructure\LogService::safeErrorLog('[GAMIFICATION] Erro ao buscar conquistas: ' . $e->getMessage());
            \Application\Services\Infrastructure\LogService::safeErrorLog('[GAMIFICATION] Stack trace: ' . $e->getTraceAsString());

            return Response::errorResponse('Erro ao buscar conquistas', 500);
        }
    }

    /**
     * GET /api/gamification/achievements/pending
     * Retorna conquistas desbloqueadas que ainda não foram vistas.
     */
    public function getPendingAchievements(): Response
    {
        $this->requireApiUserIdAndReleaseSessionOrFail();

        try {
            $pendingAchievements = UserAchievement::with('achievement')
                ->where('user_id', $this->userId)
                ->where('notification_seen', false)
                ->get()
                ->map(static function ($userAchievement): array {
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

            return Response::successResponse([
                'pending' => $pendingAchievements,
                'count' => count($pendingAchievements),
            ], 'Conquistas pendentes de notificação');
        } catch (Throwable $e) {
            \Application\Services\Infrastructure\LogService::safeErrorLog('[GAMIFICATION] Erro ao buscar conquistas pendentes: ' . $e->getMessage());

            return Response::errorResponse('Erro ao buscar conquistas pendentes', 500);
        }
    }

    /**
     * POST /api/gamification/achievements/mark-seen
     * Marca conquistas como vistas.
     */
    public function markAchievementsSeen(): Response
    {
        $this->requireApiUserIdOrFail();

        try {
            $payload = $this->getRequestPayload();
            $achievementIds = $payload['achievement_ids'] ?? [];

            if (empty($achievementIds) || !is_array($achievementIds)) {
                return Response::errorResponse('IDs de conquistas inválidos', 400);
            }

            $updated = UserAchievement::where('user_id', $this->userId)
                ->whereIn('achievement_id', $achievementIds)
                ->update(['notification_seen' => true]);

            return Response::successResponse([
                'marked_count' => $updated,
            ], 'Conquistas marcadas como vistas');
        } catch (Throwable $e) {
            \Application\Services\Infrastructure\LogService::safeErrorLog('[GAMIFICATION] Erro ao marcar conquistas: ' . $e->getMessage());

            return Response::errorResponse('Erro ao marcar conquistas', 500);
        }
    }

    /**
     * GET /api/gamification/leaderboard
     * Retorna ranking dos top usuários.
     */
    public function getLeaderboard(): Response
    {
        $user = $this->requireApiUserAndReleaseSessionOrFail();

        try {
            if (!$user->isPro()) {
                return Response::errorResponse('Recurso exclusivo para assinantes Pro', 403);
            }

            $topUsers = UserProgress::with('user:id,nome,avatar')
                ->orderBy('total_points', 'desc')
                ->orderBy('current_level', 'desc')
                ->limit(10)
                ->get()
                ->map(static function ($progress, int $index): array {
                    $avatar = $progress->user->avatar ?? null;
                    $userName = trim((string) ($progress->user->nome ?? '')) ?: 'Usuário';

                    return [
                        'position' => $index + 1,
                        'user_id' => $progress->user_id,
                        'avatar' => $avatar ? rtrim(BASE_URL, '/') . '/' . $avatar : '',
                        'user_name' => $userName,
                        'total_points' => $progress->total_points,
                        'current_level' => $progress->current_level,
                        'best_streak' => $progress->best_streak,
                    ];
                });

            $userProgress = UserProgress::where('user_id', $this->userId)->first();
            $userPosition = null;

            if ($userProgress) {
                $userPosition = UserProgress::where('total_points', '>', $userProgress->total_points)
                    ->orWhere(function ($query) use ($userProgress): void {
                        $query->where('total_points', '=', $userProgress->total_points)
                            ->where('current_level', '>', $userProgress->current_level);
                    })
                    ->count() + 1;
            }

            return Response::successResponse([
                'leaderboard' => $topUsers,
                'user_position' => $userPosition,
            ], 'Ranking de usuários');
        } catch (Throwable $e) {
            \Application\Services\Infrastructure\LogService::safeErrorLog('[GAMIFICATION] Erro ao buscar leaderboard: ' . $e->getMessage());

            return Response::errorResponse('Erro ao buscar ranking', 500);
        }
    }

    /**
     * GET /api/gamification/stats
     * Retorna estatísticas gerais para o dashboard.
     */
    public function getStats(): Response
    {
        $user = $this->requireApiUserAndReleaseSessionOrFail();

        try {
            $totalLancamentos = Lancamento::where('user_id', $this->userId)->count();
            $totalCategorias = Categoria::where('user_id', $this->userId)->count();

            $mesesAtivos = Lancamento::where('user_id', $this->userId)
                ->selectRaw('COUNT(DISTINCT DATE_FORMAT(data, "%Y-%m")) as total')
                ->first()
                ->total ?? 0;

            $progress = UserProgress::where('user_id', $this->userId)->first();

            return Response::successResponse([
                'total_lancamentos' => $totalLancamentos,
                'total_categorias' => $totalCategorias,
                'meses_ativos' => $mesesAtivos,
                'pontos_total' => $progress?->total_points ?? 0,
                'nivel_atual' => $progress?->current_level ?? 1,
                'streak_atual' => $progress?->current_streak ?? 0,
                'is_pro' => $user->isPro(),
            ], 'Estatísticas do usuário');
        } catch (Throwable $e) {
            \Application\Services\Infrastructure\LogService::safeErrorLog('[GAMIFICATION] Erro ao buscar estatisticas: ' . $e->getMessage());

            return Response::errorResponse('Erro ao buscar estatisticas', 500);
        }
    }

    /**
     * GET /api/gamification/history
     * Retorna histórico de atividades recentes.
     */
    public function getHistory(): Response
    {
        $this->requireApiUserIdAndReleaseSessionOrFail();

        try {
            $limit = $this->getIntQuery('limit', 10);
            $limit = min(max($limit, 1), 50);

            $history = \Application\Models\PointsLog::where('user_id', $this->userId)
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($log): array {
                    return [
                        'id' => $log->id,
                        'action' => $log->action,
                        'points' => $log->points,
                        'description' => $log->description ?? $this->formatActionName($log->action),
                        'created_at' => $log->created_at?->format('Y-m-d H:i:s'),
                        'relative_time' => $this->getRelativeTime($log->created_at),
                    ];
                });

            return Response::successResponse([
                'history' => $history,
                'count' => $history->count(),
            ], 'Histórico de atividades');
        } catch (Throwable $e) {
            \Application\Services\Infrastructure\LogService::safeErrorLog('[GAMIFICATION] Erro ao buscar historico: ' . $e->getMessage());

            return Response::errorResponse('Erro ao buscar historico', 500);
        }
    }

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
     * GET /api/gamification/missions
     * Retorna missões diárias do usuário.
     */
    public function getMissions(): Response
    {
        $user = $this->requireApiUserAndReleaseSessionOrFail();

        try {
            $missions = $this->missionService->getDailyMissions($this->userId, $user->isPro());
            $completedCount = count(array_filter($missions, static fn(array $m): bool => $m['completed']));

            return Response::successResponse([
                'missions' => $missions,
                'total' => count($missions),
                'completed' => $completedCount,
            ], 'Missões do dia');
        } catch (Throwable $e) {
            \Application\Services\Infrastructure\LogService::safeErrorLog('[GAMIFICATION] Erro ao buscar missões: ' . $e->getMessage());

            return Response::errorResponse('Erro ao buscar missões', 500);
        }
    }

    private function getRelativeTime(?Carbon $date): string
    {
        if (!$date) {
            return '';
        }

        $now = Carbon::now();
        $diff = $date->diff($now);

        if ($diff->days === 0) {
            if ($diff->h === 0) {
                if ($diff->i === 0) {
                    return 'Agora';
                }

                return 'Há ' . $diff->i . ' min';
            }

            return 'Há ' . $diff->h . 'h';
        }

        if ($diff->days === 1) {
            return 'Ontem';
        }

        if ($diff->days < 7) {
            return 'Há ' . $diff->days . ' dias';
        }

        return $date->format('d/m/Y');
    }
}
