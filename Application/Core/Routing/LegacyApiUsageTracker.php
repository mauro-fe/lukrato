<?php

declare(strict_types=1);

namespace Application\Core\Routing;

use Application\Container\ApplicationContainer;
use Application\Core\Request;
use Application\Services\Infrastructure\CacheService;
use Application\Services\Infrastructure\LogService;
use Throwable;

class LegacyApiUsageTracker
{
    private const COUNTER_TTL_SECONDS = 172800;
    private const LOG_INTERVAL = 100;

    private CacheService $cacheService;

    public function __construct(?CacheService $cacheService = null)
    {
        $this->cacheService = ApplicationContainer::resolveOrNew($cacheService, CacheService::class);
    }

    public function track(string $method, string $routePath, string $requestedPath, Request $request): void
    {
        try {
            $counterKey = $this->counterKey($method, $routePath);
            $usageCount = (int) $this->cacheService->get($counterKey, 0) + 1;

            $this->cacheService->set($counterKey, $usageCount, self::COUNTER_TTL_SECONDS);

            if (!$this->shouldLog($usageCount)) {
                return;
            }

            LogService::info('Legacy API route consumed', [
                'type' => 'legacy_api_usage',
                'method' => $method,
                'route_path' => $routePath,
                'requested_path' => $requestedPath,
                'successor_path' => $this->successorPathFor($requestedPath),
                'usage_count' => $usageCount,
                'sampling' => $usageCount === 1 ? 'first_hit' : 'interval',
                'client_ip' => $request->ip(),
                'accept' => $request->header('accept'),
                'user_agent' => $request->header('user-agent'),
                'request_id' => LogService::currentRequestId(),
            ]);
        } catch (Throwable $e) {
            LogService::safeErrorLog('[LegacyApiUsageTracker] Failed to track legacy API usage: ' . $e->getMessage());
        }
    }

    private function counterKey(string $method, string $routePath): string
    {
        return sprintf(
            'legacy_api_usage:%s:%s:%s',
            gmdate('Y-m-d'),
            strtolower($method),
            sha1($routePath)
        );
    }

    private function shouldLog(int $usageCount): bool
    {
        return $usageCount === 1 || $usageCount % self::LOG_INTERVAL === 0;
    }

    private function successorPathFor(string $requestedPath): string
    {
        return preg_replace('#^/api/#', '/api/v1/', $requestedPath) ?? $requestedPath;
    }
}
