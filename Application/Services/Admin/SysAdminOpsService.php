<?php

declare(strict_types=1);

namespace Application\Services\Admin;

use Application\Enums\LogCategory;
use Application\Enums\LogLevel;
use Application\Services\Infrastructure\CacheService;
use Application\Services\Infrastructure\LogService;
use Application\Services\Infrastructure\MaintenanceService;
use Throwable;

class SysAdminOpsService
{
    public function __construct(
        private ?CacheService $cacheService = null
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{data:array<string, mixed>, message:string}
     */
    public function toggleMaintenance(array $payload): array
    {
        $action = (string) ($payload['action'] ?? 'toggle');
        $reason = trim((string) ($payload['reason'] ?? ''));
        $estimatedMinutes = isset($payload['estimated_minutes']) ? (int) $payload['estimated_minutes'] : null;

        if ($action === 'toggle') {
            $action = MaintenanceService::isActive() ? 'deactivate' : 'activate';
        }

        if ($action === 'activate') {
            MaintenanceService::activate($reason, $estimatedMinutes);

            return [
                'data' => [
                    'active' => true,
                    'data' => MaintenanceService::getData(),
                ],
                'message' => 'Modo manutencao ativado com sucesso.',
            ];
        }

        MaintenanceService::deactivate();

        return [
            'data' => [
                'active' => false,
            ],
            'message' => 'Modo manutencao desativado. Sistema online.',
        ];
    }

    /**
     * @return array{active:bool,data:?array}
     */
    public function getMaintenanceStatus(): array
    {
        return [
            'active' => MaintenanceService::isActive(),
            'data' => MaintenanceService::getData(),
        ];
    }

    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    public function getErrorLogs(array $query): array
    {
        $resolvedParam = $query['resolved'] ?? null;
        $resolved = null;

        if ($resolvedParam !== null && $resolvedParam !== '') {
            $resolved = in_array((string) $resolvedParam, ['1', 'true', 'yes'], true);
        }

        $filters = [
            'level' => $query['level'] ?? null,
            'category' => $query['category'] ?? null,
            'resolved' => $resolved,
            'user_id' => isset($query['user_id']) ? (int) $query['user_id'] : null,
            'search' => $query['search'] ?? null,
            'from' => $query['date_from'] ?? null,
            'to' => $query['date_to'] ?? null,
        ];

        $page = max(1, (int) ($query['page'] ?? 1));
        $perPage = min(100, max(10, (int) ($query['per_page'] ?? 25)));

        return LogService::query($filters, $page, $perPage);
    }

    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    public function getErrorLogsSummary(array $query): array
    {
        $hours = max(1, (int) ($query['hours'] ?? 24));
        $summary = LogService::summary($hours);
        $summary['filters'] = [
            'levels' => array_map(static fn(LogLevel $level) => [
                'value' => $level->value,
                'label' => $level->label(),
                'color' => $level->color(),
                'icon' => $level->icon(),
            ], LogLevel::cases()),
            'categories' => array_map(static fn(LogCategory $category) => [
                'value' => $category->value,
                'label' => $category->label(),
            ], LogCategory::cases()),
        ];

        return $summary;
    }

    public function resolveErrorLog(int $id, int $userId): bool
    {
        return LogService::resolve($id, $userId);
    }

    public function cleanupErrorLogs(int $days, bool $includeUnresolved = false): int
    {
        return LogService::cleanup(max(7, $days), $includeUnresolved);
    }

    /**
     * @return array{details:array{files:int,redis:bool},message:string}
     */
    public function clearCache(): array
    {
        $results = [
            'files' => 0,
            'redis' => false,
        ];

        $cacheDir = BASE_PATH . '/storage/cache';
        if (is_dir($cacheDir)) {
            $results['files'] = $this->clearCacheDirectory($cacheDir);
        }

        try {
            $cache = $this->cacheService ??= new CacheService();
            if ($cache->isEnabled()) {
                $cache->flush();
                $results['redis'] = true;
            }
        } catch (Throwable) {
            $results['redis'] = false;
        }

        return [
            'details' => $results,
            'message' => "Cache limpo com sucesso ({$results['files']} arquivo(s) removido(s))",
        ];
    }

    private function clearCacheDirectory(string $cacheDir): int
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($cacheDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        $count = 0;
        foreach ($files as $fileInfo) {
            $action = $fileInfo->isDir() ? 'rmdir' : 'unlink';
            if (@$action($fileInfo->getRealPath())) {
                $count++;
            }
        }

        return $count;
    }
}
