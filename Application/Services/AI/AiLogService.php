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

    /** Cache estático para evitar hasTable() a cada chamada */
    private static ?bool $tableExists = null;

    public static function log(array $data): ?AiLog
    {
        try {
            if (self::$tableExists === null) {
                self::$tableExists = DB::schema()->hasTable('ai_logs');
            }
            if (!self::$tableExists) {
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

        $recentes = AiLog::where('created_at', '>=', $since)
            ->orderByDesc('created_at')
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

    /**
     * Métricas de qualidade semântica da IA.
     * Separa sucesso técnico de qualidade real do pipeline.
     */
    public static function qualityMetrics(int $hours = 24): array
    {
        $since = now()->subHours($hours);
        $total = AiLog::where('created_at', '>=', $since)->count();

        if ($total === 0) {
            return [
                'period_hours'         => $hours,
                'total'                => 0,
                'low_confidence_rate'  => 0.0,
                'fallback_to_chat_rate' => 0.0,
                'intent_distribution'  => [],
                'error_by_handler'     => [],
                'source_distribution'  => [],
                'avg_response_time_by_type' => [],
            ];
        }

        // Taxa de fallback para chat (indica que regras não capturaram o intent)
        $chatFallback = AiLog::where('created_at', '>=', $since)
            ->where('type', 'chat')
            ->count();

        // Distribuição por intent/type
        $intentDist = AiLog::where('created_at', '>=', $since)
            ->select('type', DB::raw('COUNT(*) as qtd'))
            ->groupBy('type')
            ->get()
            ->mapWithKeys(fn($r) => [$r->type => (int) $r->qtd])
            ->toArray();

        // Erros por handler/type
        $errorsByHandler = AiLog::where('created_at', '>=', $since)
            ->where('success', false)
            ->select('type', DB::raw('COUNT(*) as qtd'))
            ->groupBy('type')
            ->get()
            ->mapWithKeys(fn($r) => [$r->type => (int) $r->qtd])
            ->toArray();

        // Distribuição por source (rule, llm, cache, computed)
        $sourceDist = AiLog::where('created_at', '>=', $since)
            ->whereNotNull('source')
            ->select('source', DB::raw('COUNT(*) as qtd'))
            ->groupBy('source')
            ->get()
            ->mapWithKeys(fn($r) => [$r->source => (int) $r->qtd])
            ->toArray();

        // Tempo médio de resposta por tipo
        $avgTimeByType = AiLog::where('created_at', '>=', $since)
            ->select('type', DB::raw('AVG(response_time_ms) as avg_ms'))
            ->groupBy('type')
            ->get()
            ->mapWithKeys(fn($r) => [$r->type => (int) $r->avg_ms])
            ->toArray();

        // Low confidence: respostas com confidence abaixo de 0.6 (do metadata)
        $lowConfCount = AiLog::where('created_at', '>=', $since)
            ->where('confidence', '<', 0.6)
            ->where('confidence', '>', 0)
            ->count();

        return [
            'period_hours'              => $hours,
            'total'                     => $total,
            'low_confidence_rate'       => round(($lowConfCount / $total) * 100, 1),
            'fallback_to_chat_rate'     => round(($chatFallback / $total) * 100, 1),
            'intent_distribution'       => $intentDist,
            'error_by_handler'          => $errorsByHandler,
            'source_distribution'       => $sourceDist,
            'avg_response_time_by_type' => $avgTimeByType,
        ];
    }

    public static function cleanup(int $days = 90): int
    {
        $cutoff = now()->subDays($days);
        return AiLog::where('created_at', '<', $cutoff)->delete();
    }
}
