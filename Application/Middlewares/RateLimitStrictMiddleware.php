<?php

namespace Application\Middlewares;

use Application\Core\Request;
use Application\Core\Exceptions\ValidationException;
use Application\Services\Importacao\ImportSecurityPolicy;
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
    private const DEFAULT_MAX_ATTEMPTS = 10;
    private const DEFAULT_TIME_WINDOW = 60;

    private CacheService $cacheService;

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    public function handle(Request $request, string $identifier, string $endpoint = 'strict'): void
    {
        $now = time();
        [$resolvedEndpoint, $maxAttempts, $timeWindow, $tier] = $this->resolvePolicy($endpoint);
        $cacheKey = "ratelimit_strict:{$resolvedEndpoint}:{$identifier}";

        $attempts = $this->cacheService->get($cacheKey, []);
        $attempts = array_filter($attempts, fn($time) => ($now - $time) < $timeWindow);

        if (count($attempts) >= $maxAttempts) {
            LogService::persist(
                level: LogLevel::WARNING,
                category: LogCategory::SECURITY,
                message: 'Rate limit ESTRITO excedido (endpoint sensível)',
                context: [
                    'ip'         => $request->ip(),
                    'identifier' => $identifier,
                    'endpoint'   => $resolvedEndpoint,
                    'uri'        => $_SERVER['REQUEST_URI'] ?? '',
                    'method'     => $_SERVER['REQUEST_METHOD'] ?? '',
                    'attempts'   => count($attempts),
                    'limit'      => $maxAttempts,
                    'window'     => $timeWindow . 's',
                    'tier'       => $tier,
                ],
            );

            throw new ValidationException(
                ['rate_limit' => 'Limite de requisições excedido para esta operação. Aguarde um momento.'],
                'Validation failed',
                429
            );
        }

        $attempts[] = $now;
        $stored = $this->cacheService->set($cacheKey, $attempts, $timeWindow);

        if ($stored) {
            return;
        }

        LogService::persist(
            level: LogLevel::ERROR,
            category: LogCategory::SECURITY,
            message: 'Falha ao persistir estado de rate limit estrito',
            context: [
                'ip' => $request->ip(),
                'identifier' => $identifier,
                'endpoint' => $resolvedEndpoint,
                'uri' => $_SERVER['REQUEST_URI'] ?? '',
                'method' => $_SERVER['REQUEST_METHOD'] ?? '',
                'tier' => $tier,
            ],
        );

        throw new ValidationException(
            ['rate_limit' => 'Protecao temporariamente indisponivel. Tente novamente em instantes.'],
            'Validation failed',
            429
        );
    }

    public static function getIdentifier(Request $request): string
    {
        return $request->ip();
    }

    /**
     * @return array{0:string,1:int,2:int,3:string}
     */
    private function resolvePolicy(string $endpoint): array
    {
        $path = $this->normalizeRequestPath();

        if (in_array($path, ['/api/importacoes/preview', '/api/importacoes/confirm'], true)) {
            return [
                'import-upload',
                ImportSecurityPolicy::importRateLimitAttempts(),
                ImportSecurityPolicy::importRateLimitWindow(),
                'import-upload',
            ];
        }

        return [$endpoint, self::DEFAULT_MAX_ATTEMPTS, self::DEFAULT_TIME_WINDOW, 'strict'];
    }

    private function normalizeRequestPath(): string
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '/';

        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $basePath = str_replace('/index.php', '', dirname($scriptName));
        $basePath = rtrim($basePath, '/');

        if ($basePath !== '' && strpos($path, $basePath) === 0) {
            $path = substr($path, strlen($basePath));
        }

        return rtrim($path, '/') ?: '/';
    }
}
