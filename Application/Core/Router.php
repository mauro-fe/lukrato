<?php

declare(strict_types=1);

namespace Application\Core;

use Application\Core\Exceptions\HttpResponseException;
use Application\Core\Routing\ErrorResponseFactory as RoutingErrorResponseFactory;
use Application\Core\Routing\HttpExceptionHandler;
use Application\Core\Routing\MiddlewareResolver as RoutingMiddlewareResolver;
use Throwable;

class Router
{
    private static array $routes = [];
    private static ?RoutingMiddlewareResolver $middlewareResolver = null;

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
        RoutingMiddlewareResolver::reset();
    }

    public static function run(string $requestedPath, string $requestMethod): ?Response
    {
        $request = new Request();
        $routeContext = null;

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

            return self::executeCallback($routeContext, $match['params'], $request);
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

            $instance = new $controllerNs();

            if (!method_exists($instance, $method)) {
                throw new \Exception("Método '{$method}' não encontrado no controlador '{$controllerNs}'.");
            }

            $result = call_user_func_array([$instance, $method], $params);

            return $result instanceof Response ? $result : null;
        }

        throw new \Exception('Callback da rota inválida.');
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
        return self::$middlewareResolver ??= new RoutingMiddlewareResolver();
    }

    private static function exceptionHandler(): HttpExceptionHandler
    {
        return new HttpExceptionHandler(self::errorResponseFactory());
    }

    private static function errorResponseFactory(): RoutingErrorResponseFactory
    {
        return new RoutingErrorResponseFactory();
    }
}
