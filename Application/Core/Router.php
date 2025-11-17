<?php

declare(strict_types=1);

namespace Application\Core;

use Application\Services\CacheService;
use Application\Services\LogService;
use Application\Core\Exceptions\AuthException;
use Application\Core\Exceptions\ValidationException;
use Throwable;

class Router
{
    /** @var array<int, array{method: string, path: string, callback: mixed, middlewares: string[]}> */
    private static array $routes = [];

    public static function add(string $method, string $path, mixed $callback, array $middlewares = []): void
    {
        self::$routes[] = [
            'method'      => strtoupper($method),
            'path'        => trim($path, '/'),
            'callback'    => $callback,
            'middlewares' => $middlewares,
        ];
    }

    public static function run(string $requestedPath, string $requestMethod): void
    {
        $routeContext = null;

        try {
            $request = new Request();
            $match   = self::findMatchingRoute($requestedPath, $requestMethod);

            if ($match === null) {
                self::handleNotFound($request);
                return;
            }

            $routeContext = $match['route'];

            self::executeMiddlewares($routeContext['middlewares'], $request);
            self::executeCallback($routeContext, $match['params'], $request);
        } catch (AuthException $e) {
            self::handleAuthOrValidationException($e, $request ?? null);
        } catch (ValidationException $e) {
            self::handleAuthOrValidationException($e, $request ?? null);
        } catch (Throwable $e) {
            self::handleException($e, $routeContext, $request ?? null);
        }
    }

    private static function findMatchingRoute(string $path, string $method): ?array
    {
        $path = $path === '' ? '/' : trim($path, '/');

        foreach (self::$routes as $route) {
            $pattern = "#^" . preg_replace('/\{[a-zA-Z0-9_]+\}/', '([a-zA-Z0-9_-]+)', $route['path']) . "$#";

            if ($method === $route['method'] && preg_match($pattern, $path, $matches)) {
                array_shift($matches); // remove full match
                return ['route' => $route, 'params' => $matches];
            }
        }
        return null;
    }

    /** @throws ValidationException|AuthException|Throwable */
    private static function executeMiddlewares(array $middlewareNames, Request $request): void
    {
        if (empty($middlewareNames)) {
            return;
        }

        $registry = require BASE_PATH . '/Application/Middlewares/RegistryMiddleware.php';

        foreach ($middlewareNames as $name) {
            if (!isset($registry[$name])) {
                throw new \Exception("Middleware '{$name}' n├úo est├í registrado.");
            }

            $middlewareClass = $registry[$name];

            // Inje├º├úo de depend├¬ncia manual (simplificada) para middleware que precisa de cache
            if ($name === 'ratelimit') {
                (new $middlewareClass(new CacheService()))->handle($request);
            } else {
                $middlewareClass::handle($request);
            }
        }
    }

    private static function executeCallback(array $route, array $params, Request $request): void
    {
        if (is_callable($route['callback'])) {
            array_unshift($params, $request);
            call_user_func_array($route['callback'], $params);
            return;
        }

        if (is_string($route['callback']) && str_contains($route['callback'], '@')) {
            [$controllerPath, $method] = explode('@', $route['callback'], 2);
            $controllerNs = 'Application\\Controllers\\' . str_replace('/', '\\', $controllerPath);

            if (!class_exists($controllerNs)) {
                throw new \Exception("Controlador '{$controllerNs}' n├úo encontrado.");
            }

            $instance = new $controllerNs(); // Assume que BaseController lida com depend├¬ncias

            if (!method_exists($instance, $method)) {
                throw new \Exception("M├®todo '{$method}' n├úo encontrado no controlador '{$controllerNs}'.");
            }

            call_user_func_array([$instance, $method], $params);
            return;
        }

        throw new \Exception('Callback da rota inv├ílido.');
    }

    /**
     * Trata exce├º├Áes de API (Auth/Validation).
     * O router sempre responde com JSON para exce├º├Áes, pois redirecionamentos
     * devem ser tratados pelo controller (requireAuth) ou pelo cliente.
     */
    private static function handleAuthOrValidationException(\Exception $e, ?Request $request): void
    {
        if ($e instanceof AuthException) {
            Response::unauthorized($e->getMessage());
            return;
        }
        if ($e instanceof ValidationException) {
            Response::validationError($e->getErrors());
            return;
        }
    }

    public static function handleException(Throwable $e, ?array $routeContext, ?Request $request): void
    {
        LogService::critical('Erro fatal no Router', [
            'erro' => $e->getMessage(),
            'rota' => $routeContext['path'] ?? 'desconhecida',
            'trace' => $e->getTraceAsString(),
        ]);

        $isDev = (($_ENV['APP_ENV'] ?? 'production') !== 'development');
        $wantsJson = $request?->wantsJson() || $request?->isAjax();

        // Prioriza JSON se for API
        if ($wantsJson && !$isDev) {
            Response::error('Erro interno no servidor.', 500);
            return;
        }

        if ($isDev) {
            // Resposta de debug detalhada (HTML)
            $html = '<h1>Erro na Aplica├º├úo</h1><pre>';
            $html .= '<strong>Mensagem:</strong> ' . htmlspecialchars($e->getMessage()) . "\n\n";
            $html .= '<strong>Arquivo:</strong> ' . $e->getFile() . ' (Linha ' . $e->getLine() . ")\n\n";
            $html .= '<strong>Trace:</strong>' . "\n" . htmlspecialchars($e->getTraceAsString());
            $html .= '</pre>';
            Response::htmlOut($html, 500);
        } else {
            // P├ígina 500 amig├ível (HTML)
            self::handleViewError(500, BASE_PATH . '/views/errors/500.php', 'Erro no servidor');
        }
    }

    private static function handleNotFound(?Request $request): void
    {
        $wantsJson = $request?->wantsJson() || $request?->isAjax();

        if ($wantsJson) {
            Response::notFound('Recurso n├úo encontrado');
            return;
        }

        self::handleViewError(404, BASE_PATH . '/views/errors/404.php', 'P├ígina n├úo encontrada');
    }

    /**
     * Helper para incluir arquivos de view de erro.
     */
    private static function handleViewError(int $code, string $viewPath, string $defaultMessage): void
    {
        http_response_code($code);
        if (file_exists($viewPath)) {
            include $viewPath;
        } else {
            echo "<h2>{$code} | {$defaultMessage}</h2>";
        }
        exit;
    }
}
