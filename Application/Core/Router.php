<?php

declare(strict_types=1);

namespace Application\Core;

use Application\Config\InfrastructureRuntimeConfig;
use Application\Container\ApplicationContainer;
use Application\Core\Exceptions\HttpResponseException;
use Application\Core\Routing\ErrorResponseFactory as RoutingErrorResponseFactory;
use Application\Core\Routing\HttpExceptionHandler;
use Application\Core\Routing\LegacyApiUsageTracker;
use Application\Core\Routing\MiddlewareResolver as RoutingMiddlewareResolver;
use Throwable;

class Router
{
    private static array $routes = [];
    private static ?RoutingMiddlewareResolver $middlewareResolver = null;
    private static ?RoutingErrorResponseFactory $errorResponseFactory = null;
    private static ?HttpExceptionHandler $exceptionHandler = null;
    private static ?LegacyApiUsageTracker $legacyApiUsageTracker = null;
    private static ?InfrastructureRuntimeConfig $infrastructureRuntimeConfig = null;

    public static function add(string $method, string $path, mixed $callback, array $middlewares = []): void
    {
        self::$routes[] = [
            'method' => strtoupper($method),
            'path' => self::normalizeRoutePath($path),
            'callback' => $callback,
            'middlewares' => $middlewares,
        ];
    }

    public static function reset(): void
    {
        self::$routes = [];
        self::$middlewareResolver = null;
        self::$errorResponseFactory = null;
        self::$exceptionHandler = null;
        self::$legacyApiUsageTracker = null;
        self::$infrastructureRuntimeConfig = null;
        RoutingMiddlewareResolver::reset();
    }

    public static function run(string $requestedPath, string $requestMethod): ?Response
    {
        $request = ApplicationContainer::resolveOrNew(null, Request::class);
        ApplicationContainer::bindRequest($request);
        $routeContext = null;
        $normalizedRequestedPath = self::normalizeRoutePath($requestedPath);

        try {
            $match = self::findMatchingRoute($requestedPath, strtoupper($requestMethod));

            if ($match === null) {
                return self::notFoundResponse($request);
            }

            if ($match['route'] === null) {
                return self::methodNotAllowedResponse($request, $match['allowed_methods']);
            }

            $routeContext = $match['route'];

            self::middlewareResolver()->execute($routeContext['middlewares'], $request);

            $response = self::executeCallback($routeContext, $match['params'], $request);

            self::trackLegacyApiUsage(
                $routeContext['method'],
                $routeContext['path'],
                $normalizedRequestedPath,
                $request
            );

            return self::decorateLegacyApiResponse(
                $response,
                $routeContext['path'],
                $normalizedRequestedPath
            );
        } catch (HttpResponseException $e) {
            return $e->getResponse();
        } catch (Throwable $e) {
            return self::exceptionHandler()->handle($e, $routeContext, $request);
        }
    }

    private static function findMatchingRoute(string $path, string $method): ?array
    {
        $path = self::normalizeRoutePath($path);
        $allowedMethods = [];

        foreach (self::$routes as $route) {
            $params = self::matchRoutePath($route['path'], $path);
            if ($params === null) {
                continue;
            }

            $allowedMethods[] = $route['method'];

            if ($method === $route['method']) {
                return [
                    'route' => $route,
                    'params' => $params,
                    'allowed_methods' => array_values(array_unique($allowedMethods)),
                ];
            }
        }

        if ($allowedMethods !== []) {
            return [
                'route' => null,
                'params' => [],
                'allowed_methods' => array_values(array_unique($allowedMethods)),
            ];
        }

        return null;
    }

    private static function executeCallback(array $route, array $params, Request $request): ?Response
    {
        if (is_callable($route['callback'])) {
            $result = call_user_func_array(
                $route['callback'],
                self::buildCallableArguments($route['callback'], $params, $request)
            );

            return $result instanceof Response ? $result : null;
        }

        if (is_string($route['callback']) && str_contains($route['callback'], '@')) {
            [$controllerPath, $method] = explode('@', $route['callback'], 2);
            $controllerNs = 'Application\\Controllers\\' . str_replace('/', '\\', $controllerPath);

            if (!class_exists($controllerNs)) {
                throw new \Exception("Controlador '{$controllerNs}' não encontrado.");
            }

            $instance = self::resolveControllerInstance($controllerNs);

            if (!method_exists($instance, $method)) {
                throw new \Exception("Método '{$method}' não encontrado no controlador '{$controllerNs}'.");
            }

            $result = call_user_func_array([$instance, $method], $params);

            return $result instanceof Response ? $result : null;
        }

        throw new \Exception('Callback da rota inválida.');
    }

    private static function decorateLegacyApiResponse(
        ?Response $response,
        string $routePath,
        string $requestedPath
    ): ?Response {
        if ($response === null || !self::isLegacyApiPath($routePath)) {
            return $response;
        }

        $successorPath = self::versionedApiPathFor($requestedPath);

        $headers = $response->getHeaders();
        $linkHeader = sprintf('<%s>; rel="successor-version"', $successorPath);

        if (isset($headers['Link']) && trim((string) $headers['Link']) !== '') {
            $linkHeader = $headers['Link'] . ', ' . $linkHeader;
        }

        $response
            ->header('Link', $linkHeader)
            ->header('X-Legacy-Api', 'true')
            ->header('X-Legacy-Api-Successor', $successorPath);

        $legacyApiSunsetTimestamp = self::infrastructureRuntimeConfig()->legacyApiSunsetTimestamp();

        if ($legacyApiSunsetTimestamp !== null) {
            $response
                ->header('Deprecation', '@' . $legacyApiSunsetTimestamp)
                ->header('Sunset', gmdate(DATE_RFC7231, $legacyApiSunsetTimestamp));
        }

        return $response;
    }

    private static function trackLegacyApiUsage(
        string $method,
        string $routePath,
        string $requestedPath,
        Request $request
    ): void {
        if (!self::isLegacyApiPath($routePath)) {
            return;
        }

        self::legacyApiUsageTracker()->track($method, $routePath, $requestedPath, $request);
    }

    private static function resolveControllerInstance(string $controllerNs): object
    {
        return (ApplicationContainer::getInstance() ?? ApplicationContainer::bootstrap())->make($controllerNs);
    }

    public static function exceptionResponse(Throwable $e, ?array $routeContext, ?Request $request): Response
    {
        return self::exceptionHandler()->handle($e, $routeContext, $request);
    }

    public static function notFoundResponse(?Request $request = null): Response
    {
        return self::errorResponseFactory()->notFound($request);
    }

    public static function forbiddenResponse(?Request $request = null): Response
    {
        return self::errorResponseFactory()->forbidden($request);
    }

    public static function tooManyRequestsResponse(?Request $request = null, int $retryAfter = 60): Response
    {
        return self::errorResponseFactory()->tooManyRequests($request, $retryAfter);
    }

    public static function methodNotAllowedResponse(?Request $request = null, array $allowedMethods = []): Response
    {
        return self::errorResponseFactory()->methodNotAllowed($request, $allowedMethods);
    }

    private static function normalizeRoutePath(string $path): string
    {
        $trimmed = trim($path, '/');

        return $trimmed === '' ? '/' : '/' . $trimmed;
    }

    private static function isLegacyApiPath(string $path): bool
    {
        return str_starts_with($path, '/api/') && !str_starts_with($path, '/api/v1/');
    }

    private static function versionedApiPathFor(string $path): string
    {
        if (!self::isLegacyApiPath($path)) {
            return $path;
        }

        return preg_replace('#^/api/#', '/api/v1/', $path) ?? $path;
    }

    private static function matchRoutePath(string $routePath, string $requestedPath): ?array
    {
        $pattern = self::buildRoutePattern($routePath);

        if (!preg_match($pattern, $requestedPath, $matches)) {
            return null;
        }

        array_shift($matches);

        return $matches;
    }

    private static function buildRoutePattern(string $routePath): string
    {
        if ($routePath === '/') {
            return '#^/$#';
        }

        $quotedPath = preg_quote($routePath, '#');

        return '#^' . preg_replace('/\\\\\{[a-zA-Z0-9_]+\\\\\}/', '([^/]+)', $quotedPath) . '$#';
    }

    /**
     * @param array<int, mixed> $params
     * @return array<int, mixed>
     */
    private static function buildCallableArguments(callable $callback, array $params, Request $request): array
    {
        $reflection = new \ReflectionFunction(\Closure::fromCallable($callback));
        $parameters = $reflection->getParameters();

        if ($parameters !== [] && self::shouldInjectRequestIntoCallable($parameters[0])) {
            array_unshift($params, $request);
        }

        return $params;
    }

    private static function shouldInjectRequestIntoCallable(\ReflectionParameter $parameter): bool
    {
        $type = $parameter->getType();

        if (!$type instanceof \ReflectionNamedType || $type->isBuiltin()) {
            return false;
        }

        return is_a($type->getName(), Request::class, true);
    }

    private static function middlewareResolver(): RoutingMiddlewareResolver
    {
        return self::$middlewareResolver ??= ApplicationContainer::resolveOrNew(
            null,
            RoutingMiddlewareResolver::class
        );
    }

    private static function legacyApiUsageTracker(): LegacyApiUsageTracker
    {
        return self::$legacyApiUsageTracker ??= ApplicationContainer::resolveOrNew(
            null,
            LegacyApiUsageTracker::class
        );
    }

    private static function exceptionHandler(): HttpExceptionHandler
    {
        return self::$exceptionHandler ??= ApplicationContainer::resolveOrNew(
            null,
            HttpExceptionHandler::class
        );
    }

    private static function errorResponseFactory(): RoutingErrorResponseFactory
    {
        return self::$errorResponseFactory ??= ApplicationContainer::resolveOrNew(
            null,
            RoutingErrorResponseFactory::class
        );
    }

    private static function infrastructureRuntimeConfig(): InfrastructureRuntimeConfig
    {
        return self::$infrastructureRuntimeConfig ??= ApplicationContainer::resolveOrNew(
            null,
            InfrastructureRuntimeConfig::class
        );
    }
}
