<?php

declare(strict_types=1);

namespace Application\Services\AI\Collectors;

use Application\Models\Achievement;
use Application\Models\UserAchievement;
use Application\Models\UserProgress;
use Application\Services\AI\DTO\ContextPeriod;
use Application\Services\AI\Interfaces\ContextCollectorInterface;
use Illuminate\Database\Capsule\Manager as DB;

class GamificacaoCollector implements ContextCollectorInterface
{
    public function collect(ContextPeriod $period): array
    {
        $totalProgresso = UserProgress::count();

        $mediaLevel  = $totalProgresso > 0 ? round((float) UserProgress::avg('current_level'), 1) : 0;
        $mediaPontos = $totalProgresso > 0 ? round((float) UserProgress::avg('total_points'), 0) : 0;
        $mediaStreak = $totalProgresso > 0 ? round((float) UserProgress::avg('current_streak'), 1) : 0;
        $maiorStreak = (int) UserProgress::max('best_streak');

        $totalAchievements  = Achievement::where('active', 1)->count();
        $totalDesbloqueados = UserAchievement::count();
        $usersComConquista  = UserAchievement::distinct('user_id')->count('user_id');

        $nivelDist = UserProgress::select('current_level', DB::raw('COUNT(*) as qtd'))
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
            ],
        ];
    }
}
