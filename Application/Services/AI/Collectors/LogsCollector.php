<?php

declare(strict_types=1);

namespace Application\Services\AI\Collectors;

use Application\Models\ErrorLog;
use Application\Services\AI\DTO\ContextPeriod;
use Application\Services\AI\Interfaces\ContextCollectorInterface;
use Illuminate\Database\Capsule\Manager as DB;

class LogsCollector implements ContextCollectorInterface
{
    public function collect(ContextPeriod $period): array
    {
        $total        = ErrorLog::count();
        $naoResolvido = ErrorLog::whereNull('resolved_at')->count();

        $ultimas24h = ErrorLog::where('created_at', '>=', $period->now->copy()->subDay())->count();
        $ultimaSemana = ErrorLog::where('created_at', '>=', $period->now->copy()->subWeek())->count();

        $porNivel = ErrorLog::whereNull('resolved_at')
            ->select('level', DB::raw('COUNT(*) as qtd'))
            ->groupBy('level')
            ->get()
            ->mapWithKeys(fn($r) => [$r->level => (int) $r->qtd])
            ->toArray();

        $porCategoria = ErrorLog::whereNull('resolved_at')
            ->select('category', DB::raw('COUNT(*) as qtd'))
            ->groupBy('category')
            ->orderByDesc('qtd')
            ->limit(10)
            ->get()
            ->mapWithKeys(fn($r) => [$r->category ?? 'geral' => (int) $r->qtd])
            ->toArray();

        $recentes = ErrorLog::whereNull('resolved_at')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn($r) => [
                'nivel'     => $r->level,
                'categoria' => $r->category,
                'mensagem'  => mb_substr($r->message, 0, 120),
                'quando'    => $r->created_at,
            ])->toArray();

        return [
            'logs_sistema' => [
                'total_registros'          => $total,
                'nao_resolvidos'           => $naoResolvido,
                'erros_ultimas_24h'        => $ultimas24h,
                'erros_ultima_semana'      => $ultimaSemana,
                'por_nivel_nao_resolvidos' => $porNivel,
                'por_categoria'            => $porCategoria,
                'ultimos_erros'            => $recentes,
            ],
        ];
    }
}
