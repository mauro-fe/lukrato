<?php

declare(strict_types=1);

namespace Application\Services\AI\Security;

use Application\Container\ApplicationContainer;
use Application\Enums\LogCategory;
use Application\Enums\LogLevel;
use Application\Services\Infrastructure\CacheService;
use Application\Services\Infrastructure\LogService;

/**
 * Rate limiter focado no subsistema de IA.
 *
 * Usa CacheService para funcionar com Redis ou file-cache fallback.
 * A API retorna bool para que camadas diferentes decidam se bloqueiam
 * com exceção, resposta HTTP ou apenas descartem a requisição.
 */
final class AIRateLimiter
{
    private readonly CacheService $cache;

    public function __construct(?CacheService $cache = null)
    {
        $this->cache = ApplicationContainer::resolveOrNew($cache, CacheService::class);
    }

    public function allow(
        string $scope,
        string $bucket,
        string $identifier,
        int $maxAttempts,
        int $windowSeconds,
        array $context = [],
    ): bool {
        $now = time();
        $cacheKey = $this->buildCacheKey($scope, $bucket, $identifier);

        $attempts = $this->cache->get($cacheKey, []);
        if (!is_array($attempts)) {
            $attempts = [];
        }

        $attempts = array_values(array_filter(
            $attempts,
            static fn($timestamp): bool => is_int($timestamp) && ($now - $timestamp) < $windowSeconds,
        ));

        if (count($attempts) >= $maxAttempts) {
            LogService::persist(
                level: LogLevel::WARNING,
                category: LogCategory::SECURITY,
                message: 'AI rate limit excedido',
                context: array_merge($context, [
                    'scope'    => $scope,
                    'bucket'   => $bucket,
                    'limit'    => $maxAttempts,
                    'window_s' => $windowSeconds,
                    'attempts' => count($attempts),
                ]),
            );

            return false;
        }

        $attempts[] = $now;
        $this->cache->set($cacheKey, $attempts, $windowSeconds);

        return true;
    }

    private function buildCacheKey(string $scope, string $bucket, string $identifier): string
    {
        return 'ai:rate_limit:' . $scope . ':' . $bucket . ':' . sha1($identifier);
    }
}
