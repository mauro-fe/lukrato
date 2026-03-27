<?php

declare(strict_types=1);

namespace Application\Services\Gamification;

use Application\Models\PointsLog;
use Application\Models\UserProgress;
use Carbon\Carbon;

/**
 * Service: MissionService
 *
 * Computa missões diárias a partir de dados existentes (points_log, user_progress).
 * Sem tabela própria — missões são derivadas e recalculadas a cada request.
 */
class MissionService
{
    /**
     * Retorna missões do dia para o usuário.
     *
     * @return list<array{id: string, title: string, description: string, icon: string, points_reward: int, progress: array{current: int, target: int}, completed: bool}>
     */
    public function getDailyMissions(int $userId, bool $isPro): array
    {
        $today = Carbon::today()->toDateString();

        $todayLogs = PointsLog::where('user_id', $userId)
            ->whereDate('created_at', $today)
            ->get();

        $todayActions = [];
        foreach ($todayLogs as $log) {
            $action = strtolower($log->action);
            $todayActions[$action] = ($todayActions[$action] ?? 0) + 1;
        }

        $progress = UserProgress::where('user_id', $userId)->first();

        $missions = [];

        // Mission 1: Registrar lançamentos hoje
        $lancTarget = $isPro ? 5 : 3;
        $lancCurrent = $todayActions['create_lancamento'] ?? 0;
        $lancReward = $isPro ? 45 : 30;
        $missions[] = [
            'id'            => 'daily_lancamentos',
            'title'         => "Registrar {$lancTarget} lançamentos",
            'description'   => 'Mantenha seus registros em dia',
            'icon'          => 'coins',
            'points_reward' => $lancReward,
            'progress'      => ['current' => min($lancCurrent, $lancTarget), 'target' => $lancTarget],
            'completed'     => $lancCurrent >= $lancTarget,
        ];

        // Mission 2: Manter streak ativo
        $hasActivity = ($todayActions['daily_activity'] ?? 0) > 0
                    || ($todayActions['create_lancamento'] ?? 0) > 0;
        $missions[] = [
            'id'            => 'daily_streak',
            'title'         => 'Manter seu streak ativo',
            'description'   => 'Registre pelo menos uma atividade hoje',
            'icon'          => 'flame',
            'points_reward' => $isPro ? 10 : 5,
            'progress'      => ['current' => $hasActivity ? 1 : 0, 'target' => 1],
            'completed'     => $hasActivity,
        ];

        // Mission 3: Visualizar um relatório
        $reportCurrent = $todayActions['view_report'] ?? 0;
        $missions[] = [
            'id'            => 'daily_report',
            'title'         => 'Visualizar um relatório',
            'description'   => 'Acompanhe como estão suas finanças',
            'icon'          => 'bar-chart-3',
            'points_reward' => $isPro ? 25 : 10,
            'progress'      => ['current' => min($reportCurrent, 1), 'target' => 1],
            'completed'     => $reportCurrent >= 1,
        ];

        // Mission 4 (Pro only): Categorizar lançamentos
        if ($isPro) {
            $catCurrent = $todayActions['create_categoria'] ?? 0;
            $missions[] = [
                'id'            => 'daily_categoria',
                'title'         => 'Criar uma nova categoria',
                'description'   => 'Organize melhor suas finanças',
                'icon'          => 'tag',
                'points_reward' => 30,
                'progress'      => ['current' => min($catCurrent, 1), 'target' => 1],
                'completed'     => $catCurrent >= 1,
            ];
        }

        return $missions;
    }
}
