<?php

declare(strict_types=1);

namespace Application\Services\AI\Collectors;

use Application\Models\LogWebhookCobranca;
use Application\Services\AI\DTO\ContextPeriod;
use Application\Services\AI\Interfaces\ContextCollectorInterface;
use Illuminate\Database\Capsule\Manager as DB;

class WebhooksCollector implements ContextCollectorInterface
{
    public function collect(ContextPeriod $period): array
    {
        $total     = LogWebhookCobranca::count();
        $ultimas24 = LogWebhookCobranca::where('created_at', '>=', $period->now->copy()->subDay())->count();
        $semana    = LogWebhookCobranca::where('created_at', '>=', $period->now->copy()->subWeek())->count();

        $porEvento = LogWebhookCobranca::select('tipo_evento', DB::raw('COUNT(*) as qtd'))
            ->groupBy('tipo_evento')
            ->orderByDesc('qtd')
            ->limit(10)
            ->get()
            ->mapWithKeys(fn($r) => [$r->tipo_evento ?? 'desconhecido' => (int) $r->qtd])
            ->toArray();

        $porProvedor = LogWebhookCobranca::select('provedor', DB::raw('COUNT(*) as qtd'))
            ->groupBy('provedor')
            ->get()
            ->mapWithKeys(fn($r) => [$r->provedor ?? 'desconhecido' => (int) $r->qtd])
            ->toArray();

        $recentes = LogWebhookCobranca::orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn($r) => [
                'provedor'   => $r->provedor,
                'evento'     => $r->tipo_evento,
                'quando'     => $r->created_at,
            ])->toArray();

        return [
            'webhooks_cobranca' => [
                'total_registros'     => $total,
                'ultimas_24h'         => $ultimas24,
                'ultima_semana'       => $semana,
                'por_tipo_evento'     => $porEvento,
                'por_provedor'        => $porProvedor,
                'ultimos_recebidos'   => $recentes,
            ],
        ];
    }
}
