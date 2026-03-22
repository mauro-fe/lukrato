<?php

declare(strict_types=1);

namespace Application\Services\Admin;

use Application\Services\AI\AiLogService;

class AiLogsAdminWorkflowService
{
    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    public function index(array $query): array
    {
        $filters = [
            'type' => $query['type'] ?? null,
            'channel' => $query['channel'] ?? null,
            'success' => $query['success'] ?? '',
            'search' => $query['search'] ?? null,
            'date_from' => $query['date_from'] ?? null,
            'date_to' => $query['date_to'] ?? null,
            'page' => $query['page'] ?? 1,
            'per_page' => $query['per_page'] ?? 20,
        ];

        return [
            'success' => true,
            'data' => AiLogService::query($filters),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(mixed $hours): array
    {
        return [
            'success' => true,
            'data' => AiLogService::summary(max(1, (int) $hours)),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function cleanup(array $payload): array
    {
        $days = max(1, (int) ($payload['days'] ?? 90));
        $deleted = AiLogService::cleanup($days);

        return [
            'success' => true,
            'data' => [
                'deleted' => $deleted,
                'message' => "Removidos {$deleted} registros com mais de {$days} dias.",
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function quality(mixed $hours): array
    {
        return [
            'success' => true,
            'data' => AiLogService::qualityMetrics(max(1, (int) $hours)),
        ];
    }
}
