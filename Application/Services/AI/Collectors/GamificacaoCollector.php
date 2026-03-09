<?php

declare(strict_types=1);

namespace Application\Services\AI\Collectors;

use Application\Models\Achievement;
use Application\Models\PointsLog;
use Application\Models\UserAchievement;
use Application\Models\UserProgress;
use Application\Services\AI\DTO\ContextPeriod;
use Application\Services\AI\Interfaces\ContextCollectorInterface;
use Illuminate\Database\Capsule\Manager as DB;

class GamificacaoCollector implements ContextCollectorInterface
{
    public function collect(ContextPeriod $period, ?int $userId = null): array
    {
        $progressBase = UserProgress::query();
        if ($userId) $progressBase->where('user_id', $userId);

        $totalProgresso = (clone $progressBase)->count();

        $mediaLevel  = $totalProgresso > 0 ? round((float) (clone $progressBase)->avg('current_level'), 1) : 0;
        $mediaPontos = $totalProgresso > 0 ? round((float) (clone $progressBase)->avg('total_points'), 0) : 0;
        $mediaStreak = $totalProgresso > 0 ? round((float) (clone $progressBase)->avg('current_streak'), 1) : 0;
        $maiorStreak = (int) (clone $progressBase)->max('best_streak');

        $achieveBase = UserAchievement::query();
        if ($userId) $achieveBase->where('user_id', $userId);

        $totalAchievements  = Achievement::where('active', 1)->count();
        $totalDesbloqueados = (clone $achieveBase)->count();
        $usersComConquista  = $userId ? ($totalDesbloqueados > 0 ? 1 : 0) : (clone $achieveBase)->distinct('user_id')->count('user_id');

        $nivelDist = (clone $progressBase)->select('current_level', DB::raw('COUNT(*) as qtd'))
            ->groupBy('current_level')
            ->orderBy('current_level')
            ->get()
            ->mapWithKeys(fn($r) => ['nivel_' . $r->current_level => (int) $r->qtd])
            ->toArray();

        return [
            'gamificacao' => [
                'usuarios_com_progresso'   => $totalProgresso,
                'nivel_medio'              => $mediaLevel,
                'pontos_medios'            => $mediaPontos,
                'streak_medio'             => $mediaStreak,
                'maior_streak'             => $maiorStreak,
                'conquistas_disponiveis'   => $totalAchievements,
                'conquistas_desbloqueadas' => $totalDesbloqueados,
                'usuarios_com_conquista'   => $usersComConquista,
                'distribuicao_niveis'      => $nivelDist,
                'pontos_por_acao'          => $this->pontosPorAcao($userId),
            ],
        ];
    }

    private function pontosPorAcao(?int $userId): array
    {
        $query = PointsLog::select('action', DB::raw('COUNT(*) as qtd'), DB::raw('SUM(points) as total_pontos'));
        if ($userId) $query->where('user_id', $userId);
        return $query->groupBy('action')
            ->orderByDesc('qtd')
            ->limit(10)
            ->get()
            ->map(fn($r) => [
                'acao'         => $r->action,
                'ocorrencias'  => (int) $r->qtd,
                'total_pontos' => (int) $r->total_pontos,
            ])->toArray();
    }
}
