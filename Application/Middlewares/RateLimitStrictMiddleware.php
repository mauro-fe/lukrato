<?php

namespace Application\Middlewares;

use Application\Core\Request;
use Application\Core\Exceptions\ValidationException;
use Application\Services\Infrastructure\CacheService;
use Application\Services\Infrastructure\LogService;
use Application\Enums\LogLevel;
use Application\Enums\LogCategory;

/**
 * Rate limiting rigoroso para endpoints sensíveis.
 * 
 * Usado em: billing/checkout, exclusão de conta, exports, transfers.
 * Limite: 10 requisições por minuto por IP.
 */
class RateLimitStrictMiddleware
{
    private const MAX_ATTEMPTS = 10;
    private const TIME_WINDOW = 60;

    private CacheService $cacheService;

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    public function handle(Request $request, string $identifier): void
    {
        $now = time();
        $cacheKey = "ratelimit_strict:{$identifier}";

        $attempts = $this->cacheService->get($cacheKey, []);
        $attempts = array_filter($attempts, fn($time) => ($now - $time) < self::TIME_WINDOW);

        if (count($attempts) >= self::MAX_ATTEMPTS) {
            LogService::persist(
                level: LogLevel::WARNING,
                category: LogCategory::SECURITY,
                message: 'Rate limit ESTRITO excedido (endpoint sensível)',
                context: [
                    'ip'         => $request->ip(),
                    'identifier' => $identifier,
                    'uri'        => $_SERVER['REQUEST_URI'] ?? '',
                    'method'     => $_SERVER['REQUEST_METHOD'] ?? '',
                    'attempts'   => count($attempts),
                    'limit'      => self::MAX_ATTEMPTS,
                    'window'     => self::TIME_WINDOW . 's',
                    'tier'       => 'strict',
                ],
            );

            throw new ValidationException(
                ['rate_limit' => 'Limite de requisições excedido para esta operação. Aguarde um momento.'],
                429
            );
        }

        $attempts[] = $now;
        $this->cacheService->set($cacheKey, $attempts, self::TIME_WINDOW);
    }

    public static function getIdentifier(Request $request): string
    {
        return $request->ip();
    }
}
