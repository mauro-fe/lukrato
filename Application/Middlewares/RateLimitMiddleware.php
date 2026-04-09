<?php

namespace Application\Middlewares;

use Application\Config\RateLimitRuntimeConfig;
use Application\Container\ApplicationContainer;
use Application\Core\Exceptions\HttpResponseException;
use Application\Core\Exceptions\ValidationException;
use Application\Core\Request;
use Application\Core\Router;
use Application\Enums\LogCategory;
use Application\Enums\LogLevel;
use Application\Services\Infrastructure\CacheService;
use Application\Services\Infrastructure\LogService;

class RateLimitMiddleware
{
    private int $maxAttempts;
    private int $timeWindow;
    private CacheService $cacheService;
    private bool $customMaxAttempts;
    private bool $customTimeWindow;
    private RateLimitRuntimeConfig $runtimeConfig;

    public function __construct(
        CacheService $cacheService,
        ?int $maxAttempts = null,
        ?int $timeWindow = null,
        ?RateLimitRuntimeConfig $runtimeConfig = null
    )
    {
        $this->cacheService = $cacheService;
        $this->customMaxAttempts = $maxAttempts !== null;
        $this->customTimeWindow = $timeWindow !== null;
        $this->runtimeConfig = ApplicationContainer::resolveOrNew($runtimeConfig, RateLimitRuntimeConfig::class);
        $this->maxAttempts = $maxAttempts ?? $this->runtimeConfig->maxAttempts();
        $this->timeWindow = $timeWindow ?? $this->runtimeConfig->timeWindow();
    }

    /**
     * @throws ValidationException
     */
    public function handle(Request $request, string $identifier, string $endpoint = 'global'): void
    {
        $now = time();
        [$resolvedEndpoint, $maxAttempts, $timeWindow, $tier] = $this->resolvePolicy($request, $endpoint);
        $cacheKey = "ratelimit:{$resolvedEndpoint}:{$identifier}";

        $attempts = $this->cacheService->get($cacheKey, []);
        $attempts = array_filter($attempts, fn($time) => ($now - $time) < $timeWindow);

        if (count($attempts) >= $maxAttempts) {
            LogService::persist(
                level: LogLevel::WARNING,
                category: LogCategory::SECURITY,
                message: 'Rate limit excedido',
                context: [
                    'ip' => $request->ip(),
                    'identifier' => $identifier,
                    'endpoint' => $resolvedEndpoint,
                    'uri' => $_SERVER['REQUEST_URI'] ?? '',
                    'method' => $_SERVER['REQUEST_METHOD'] ?? '',
                    'attempts' => count($attempts),
                    'limit' => $maxAttempts,
                    'window' => $timeWindow . 's',
                    'tier' => $tier,
                ],
            );

            if ($request->wantsJson() || $request->isAjax()) {
                throw new ValidationException(
                    ['rate_limit' => 'Muitas requisições. Por favor, tente novamente em breve.'],
                    'Validation failed',
                    429
                );
            }

            throw new HttpResponseException(Router::tooManyRequestsResponse($request, $timeWindow));
        }

        $attempts[] = $now;
        $stored = $this->cacheService->set($cacheKey, $attempts, $timeWindow);

        if ($stored) {
            return;
        }

        LogService::persist(
            level: LogLevel::ERROR,
            category: LogCategory::SECURITY,
            message: 'Falha ao persistir estado de rate limit',
            context: [
                'ip' => $request->ip(),
                'identifier' => $identifier,
                'endpoint' => $resolvedEndpoint,
                'uri' => $_SERVER['REQUEST_URI'] ?? '',
                'method' => $_SERVER['REQUEST_METHOD'] ?? '',
                'tier' => $tier,
            ],
        );

        if ($request->wantsJson() || $request->isAjax()) {
            throw new ValidationException(
                ['rate_limit' => 'Protecao temporariamente indisponivel. Tente novamente em instantes.'],
                'Validation failed',
                429
            );
        }

        throw new HttpResponseException(Router::tooManyRequestsResponse($request, $timeWindow));
    }

    public static function getIdentifier(Request $request): string
    {
        return $request->ip();
    }

    /**
     * @return array{0:string,1:int,2:int,3:string}
     */
    private function resolvePolicy(Request $request, string $endpoint): array
    {
        if ($this->customMaxAttempts || $this->customTimeWindow) {
            return [$endpoint, $this->maxAttempts, $this->timeWindow, 'custom'];
        }

        $path = $this->normalizeRequestPath();

        if ($path === '/login/entrar') {
            return ['auth-login', 5, 60, 'auth-login'];
        }

        if (in_array($path, ['/recuperar-senha', '/resetar-senha', '/verificar-email/reenviar'], true)) {
            return ['auth-sensitive', 3, 60, 'auth-sensitive'];
        }

        if ($this->isAdminPath($path)) {
            return ['admin', 20, 60, 'admin'];
        }

        return [$endpoint, $this->maxAttempts, $this->timeWindow, 'standard'];
    }

    private function isAdminPath(string $path): bool
    {
        return str_starts_with($path, '/api/sysadmin')
            || str_starts_with($path, '/api/cupons')
            || str_starts_with($path, '/api/campaigns');
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
