<?php

declare(strict_types=1);

namespace Application\Services\AI;

use Application\DTO\AI\IntentResult;
use Application\Models\AiLog;
use Illuminate\Database\Capsule\Manager as DB;

class AiLogService
{
    // Precos por 1M tokens (input / output).
    private const MODEL_PRICING = [
        'gpt-5.4'      => ['input' => 2.50, 'output' => 15.00],
        'gpt-5.2'      => ['input' => 1.75, 'output' => 14.00],
        'gpt-5.1'      => ['input' => 1.25, 'output' => 10.00],
        'gpt-5'        => ['input' => 1.25, 'output' => 10.00],
        'gpt-5-mini'   => ['input' => 0.25, 'output' => 2.00],
        'gpt-5-nano'   => ['input' => 0.05, 'output' => 0.40],
        'gpt-4.1'      => ['input' => 2.00, 'output' => 8.00],
        'gpt-4.1-mini' => ['input' => 0.40, 'output' => 1.60],
        'gpt-4.1-nano' => ['input' => 0.10, 'output' => 0.40],
        'gpt-4o-mini'  => ['input' => 0.15, 'output' => 0.60],
        'gpt-4o'       => ['input' => 2.50, 'output' => 10.00],
        'gpt-4-turbo'  => ['input' => 10.00, 'output' => 30.00],
    ];

    private const DEFAULT_PRICING_MODEL = 'gpt-4o-mini';

    /** Cache estatico para evitar hasTable() a cada chamada. */
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

        $total = AiLog::where('created_at', '>=', $since)->count();
        $successCount = AiLog::where('created_at', '>=', $since)
            ->where('success', true)
            ->count();

        $byType = AiLog::where('created_at', '>=', $since)
            ->select('type', DB::raw('COUNT(*) as qtd'), DB::raw('SUM(tokens_total) as tokens'))
            ->groupBy('type')
            ->get()
            ->mapWithKeys(fn($row) => [
                $row->type => [
                    'qtd' => (int) $row->qtd,
                    'tokens' => (int) $row->tokens,
                ],
            ])
            ->toArray();

        $tokensTotal = AiLog::where('created_at', '>=', $since)->sum('tokens_total');
        $avgTime = (int) AiLog::where('created_at', '>=', $since)
            ->where('response_time_ms', '>', 0)
            ->avg('response_time_ms');

        $estimatedCost = AiLog::where('created_at', '>=', $since)
            ->get(['model', 'tokens_prompt', 'tokens_completion', 'tokens_total'])
            ->reduce(
                fn(float $carry, AiLog $log): float => $carry + self::estimateLogCost($log),
                0.0
            );

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
     * Metricas de qualidade semantica da IA.
     * Separa sucesso tecnico de qualidade real do pipeline.
     */
    public static function qualityMetrics(int $hours = 24): array
    {
        $since = now()->subHours($hours);
        $total = AiLog::where('created_at', '>=', $since)->count();
        $confidenceThreshold = IntentResult::CONFIDENCE_THRESHOLD;

        if ($total === 0) {
            return [
                'period_hours'              => $hours,
                'total'                     => 0,
                'low_confidence_rate'       => 0.0,
                'fallback_to_chat_rate'     => 0.0,
                'intent_distribution'       => [],
                'error_by_handler'          => [],
                'source_distribution'       => [],
                'avg_response_time_by_type' => [],
            ];
        }

        $chatFallback = AiLog::where('created_at', '>=', $since)
            ->where('type', 'chat')
            ->where('confidence', '>', 0)
            ->where('confidence', '<', $confidenceThreshold)
            ->count();

        $intentDist = AiLog::where('created_at', '>=', $since)
            ->select('type', DB::raw('COUNT(*) as qtd'))
            ->groupBy('type')
            ->get()
            ->mapWithKeys(fn($row) => [$row->type => (int) $row->qtd])
            ->toArray();

        $errorsByHandler = AiLog::where('created_at', '>=', $since)
            ->where('success', false)
            ->select('type', DB::raw('COUNT(*) as qtd'))
            ->groupBy('type')
            ->get()
            ->mapWithKeys(fn($row) => [$row->type => (int) $row->qtd])
            ->toArray();

        $sourceDist = AiLog::where('created_at', '>=', $since)
            ->whereNotNull('source')
            ->select('source', DB::raw('COUNT(*) as qtd'))
            ->groupBy('source')
            ->get()
            ->mapWithKeys(fn($row) => [$row->source => (int) $row->qtd])
            ->toArray();

        $avgTimeByType = AiLog::where('created_at', '>=', $since)
            ->where('response_time_ms', '>', 0)
            ->select('type', DB::raw('AVG(response_time_ms) as avg_ms'))
            ->groupBy('type')
            ->get()
            ->mapWithKeys(fn($row) => [$row->type => (int) $row->avg_ms])
            ->toArray();

        $lowConfCount = AiLog::where('created_at', '>=', $since)
            ->where('confidence', '<', $confidenceThreshold)
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

    private static function estimateLogCost(AiLog $log): float
    {
        $pricing = self::resolveModelPricing((string) $log->model);
        $tokensPrompt = (int) ($log->tokens_prompt ?? 0);
        $tokensCompletion = (int) ($log->tokens_completion ?? 0);
        $tokensTotal = (int) ($log->tokens_total ?? 0);

        if ($tokensPrompt > 0 || $tokensCompletion > 0) {
            return ($tokensPrompt / 1_000_000) * $pricing['input']
                + ($tokensCompletion / 1_000_000) * $pricing['output'];
        }

        if ($tokensTotal <= 0) {
            return 0.0;
        }

        // Logs antigos de imagem/audio guardavam apenas total; tratar como input
        // evita custo zerado sem inventar um split inexistente.
        return ($tokensTotal / 1_000_000) * $pricing['input'];
    }

    /**
     * @return array{input: float, output: float}
     */
    private static function resolveModelPricing(string $model): array
    {
        $normalized = strtolower(trim($model));
        if ($normalized === '') {
            return self::MODEL_PRICING[self::DEFAULT_PRICING_MODEL];
        }

        if (isset(self::MODEL_PRICING[$normalized])) {
            return self::MODEL_PRICING[$normalized];
        }

        $modelKeys = array_keys(self::MODEL_PRICING);
        usort($modelKeys, static fn(string $left, string $right): int => strlen($right) <=> strlen($left));

        foreach ($modelKeys as $candidate) {
            if (self::matchesPricingModel($normalized, $candidate)) {
                return self::MODEL_PRICING[$candidate];
            }
        }

        return self::MODEL_PRICING[self::DEFAULT_PRICING_MODEL];
    }

    private static function matchesPricingModel(string $model, string $candidate): bool
    {
        return $model === $candidate || str_starts_with($model, $candidate . '-');
    }
}
