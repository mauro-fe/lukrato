<?php

declare(strict_types=1);

namespace Application\Services\AI;

use Application\Models\AiLog;
use Illuminate\Database\Capsule\Manager as DB;

class AiLogService
{
    // Preços por 1M tokens (input / output) — atualizar conforme pricing OpenAI
    private const MODEL_PRICING = [
        'gpt-4o-mini' => ['input' => 0.15, 'output' => 0.60],
        'gpt-4o'      => ['input' => 2.50, 'output' => 10.00],
        'gpt-4-turbo' => ['input' => 10.00, 'output' => 30.00],
    ];

    public static function log(array $data): ?AiLog
    {
        try {
            if (!DB::schema()->hasTable('ai_logs')) {
                return null;
            }

            return AiLog::create($data);
        } catch (\Throwable) {
            return null;
        }
    }

    public static function query(array $filters = []): array
    {
        $query = AiLog::query()->orderByDesc('created_at');

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['channel'])) {
            $query->where('channel', $filters['channel']);
        }

        if (isset($filters['success']) && $filters['success'] !== '') {
            $query->where('success', (bool) $filters['success']);
        }

        if (!empty($filters['search'])) {
            $term = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($term) {
                $q->where('prompt', 'LIKE', $term)
                    ->orWhere('response', 'LIKE', $term)
                    ->orWhere('error_message', 'LIKE', $term);
            });
        }

        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to'] . ' 23:59:59');
        }

        $page    = max(1, (int) ($filters['page'] ?? 1));
        $perPage = min(100, max(10, (int) ($filters['per_page'] ?? 20)));
        $total   = $query->count();

        $data = $query
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get()
            ->toArray();

        return [
            'data'     => $data,
            'total'    => $total,
            'page'     => $page,
            'per_page' => $perPage,
        ];
    }

    public static function summary(int $hours = 24): array
    {
        $since = now()->subHours($hours);

        $total       = AiLog::where('created_at', '>=', $since)->count();
        $successCount = AiLog::where('created_at', '>=', $since)->where('success', true)->count();

        $byType = AiLog::where('created_at', '>=', $since)
            ->select('type', DB::raw('COUNT(*) as qtd'), DB::raw('SUM(tokens_total) as tokens'))
            ->groupBy('type')
            ->get()
            ->mapWithKeys(fn($r) => [$r->type => ['qtd' => (int) $r->qtd, 'tokens' => (int) $r->tokens]])
            ->toArray();

        $tokensTotal = AiLog::where('created_at', '>=', $since)->sum('tokens_total');
        $avgTime     = (int) AiLog::where('created_at', '>=', $since)->avg('response_time_ms');

        // Custo estimado
        $costByModel = AiLog::where('created_at', '>=', $since)
            ->select('model', DB::raw('SUM(tokens_prompt) as inp'), DB::raw('SUM(tokens_completion) as outp'))
            ->groupBy('model')
            ->get();

        $estimatedCost = 0.0;
        foreach ($costByModel as $row) {
            $pricing = self::MODEL_PRICING[$row->model] ?? self::MODEL_PRICING['gpt-4o-mini'];
            $estimatedCost += ((int) $row->inp / 1_000_000) * $pricing['input'];
            $estimatedCost += ((int) $row->outp / 1_000_000) * $pricing['output'];
        }

        $recentes = AiLog::orderByDesc('created_at')
            ->limit(5)
            ->get(['id', 'type', 'tokens_total', 'response_time_ms', 'success', 'created_at'])
            ->toArray();

        return [
            'period_hours'   => $hours,
            'total'          => $total,
            'success_count'  => $successCount,
            'error_count'    => $total - $successCount,
            'success_rate'   => $total > 0 ? round(($successCount / $total) * 100, 1) : 100,
            'tokens_total'   => (int) $tokensTotal,
            'estimated_cost' => round($estimatedCost, 4),
            'avg_time_ms'    => $avgTime,
            'by_type'        => $byType,
            'recentes'       => $recentes,
        ];
    }

    public static function cleanup(int $days = 90): int
    {
        $cutoff = now()->subDays($days);
        return AiLog::where('created_at', '<', $cutoff)->delete();
    }
}
