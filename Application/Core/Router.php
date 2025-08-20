<?php

namespace Application\Core;

use Application\Services\CacheService;
use Application\Services\LogService;
use Application\Core\Exceptions\AuthException;
use Application\Core\Exceptions\ValidationException;

class Router
{
    private static array $routes = [];

    public static function add(string $method, string $path, $callback, array $middlewares = []): void
    {
        self::$routes[] = [
            'method'      => strtoupper($method),
            'path'        => trim($path, '/'),
            'callback'    => $callback,
            'middlewares' => $middlewares,
        ];
    }

    /**
     * Ponto de entrada do roteador.
     */
    public static function run(string $requestedPath, string $requestMethod): void
    {
        $routeContext = null;

        try {
            $request   = new Request();
            $match     = self::findMatchingRoute($requestedPath, $requestMethod);

            if ($match === null) {
                self::handleNotFound();
                return;
            }

            $routeContext = $match['route'];

            self::executeMiddlewares($routeContext['middlewares'], $request);
            self::executeCallback($routeContext, $match['params'], $request);
        } catch (AuthException | ValidationException $e) {
            self::handleAuthOrValidationException($e);
        } catch (\Throwable $e) {
            self::handleException($e, $routeContext);
        }
    }

    /**
     * Encontra a rota correspondente à requisição.
     * @return array{route: array, params: array}|null
     */
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

    /**
     * Executa os middlewares associados à rota.
     */
    private static function executeMiddlewares(array $middlewareNames, Request $request): void
    {
        if (empty($middlewareNames)) {
            return;
        }

        // registry deve retornar um array ['alias' => ClassName::class]
        $registry = require BASE_PATH . '/Application/Middlewares/RegistryMiddleware.php';

        foreach ($middlewareNames as $name) {
            if (!isset($registry[$name])) {
                throw new \Exception("Middleware '{$name}' não está registrado.");
            }

            $middlewareClass = $registry[$name];

            // Exemplo: 'ratelimit' precisa de dependência
            if ($name === 'ratelimit') {
                (new $middlewareClass(new CacheService()))->handle($request);
            } else {
                // Middlewares estáticos: public static function handle(Request $r)
                $middlewareClass::handle($request);
            }
        }
    }

    /**
     * Executa o callback (closure ou Controller@metodo).
     */
    private static function executeCallback(array $route, array $params, Request $request): void
    {
        // Closure: injeta Request como primeiro parâmetro
        if (is_callable($route['callback'])) {
            array_unshift($params, $request);
            call_user_func_array($route['callback'], $params);
            return;
        }

        // Formato "Controller/Path@metodo" relativo a Application\Controllers\
        if (is_string($route['callback'])) {
            [$controllerPath, $method] = explode('@', $route['callback']);
            $controllerNs = 'Application\\Controllers\\' . str_replace('/', '\\', $controllerPath);

            if (!class_exists($controllerNs)) {
                LogService::error('Controlador não encontrado', [
                    'controller' => $controllerNs,
                    'rota'       => $route['path'],
                    'callback'   => $route['callback'],
                ]);
                throw new \Exception("Controlador '{$controllerNs}' não encontrado.");
            }

            $instance = new $controllerNs();

            if (!method_exists($instance, $method)) {
                LogService::error('Método não encontrado no controlador', [
                    'controller' => $controllerNs,
                    'metodo'     => $method,
                    'rota'       => $route['path'],
                    'callback'   => $route['callback'],
                ]);
                throw new \Exception("Método '{$method}' não encontrado no controlador '{$controllerNs}'.");
            }

            call_user_func_array([$instance, $method], $params);
            return;
        }

        throw new \Exception('Callback da rota inválido.');
    }

    /**
     * Trata exceções de autenticação/validação.
     */
    private static function handleAuthOrValidationException(\Exception $e): void
    {
        if ($e instanceof AuthException) {
            header('Location: ' . BASE_URL . 'admin/login');
            exit;
        }

        if ($e instanceof ValidationException) {
            http_response_code($e->getCode() ?: 403);
            header('Content-Type: application/json');
            echo json_encode([
                'status'  => 'error',
                'message' => $e->getMessage(),
                'errors'  => $e->getErrors(),
            ]);
            exit;
        }
    }

    /**
     * Trata exceções genéricas (500 em prod, detalhado em dev).
     */
    public static function handleException(\Throwable $e, ?array $routeContext): void
    {
        LogService::critical('Erro fatal no Router', [
            'erro' => $e->getMessage(),
            'rota' => $routeContext,
            'trace' => $e->getTraceAsString(),
        ]);

        $isDev = (($_ENV['APP_ENV'] ?? 'production') === 'development');

        http_response_code(500);

        if ($isDev) {
            echo '<h1>Erro na Aplicação</h1><pre>';
            echo '<strong>Mensagem:</strong> ' . htmlspecialchars($e->getMessage()) . "\n\n";
            echo '<strong>Arquivo:</strong> ' . $e->getFile() . ' (Linha ' . $e->getLine() . ")\n\n";
            echo '<strong>Trace:</strong>' . "\n" . htmlspecialchars($e->getTraceAsString());
            echo '</pre>';
        } else {
            include BASE_PATH . '/views/errors/500.php';
        }
        exit;
    }

    /**
     * 404 Not Found padrão.
     */
    private static function handleNotFound(): void
    {
        http_response_code(404);
        $errorPage = BASE_PATH . '/views/errors/404.php';

        if (file_exists($errorPage)) {
            include $errorPage;
        } else {
            echo '<h2>Página não encontrada</h2>';
        }
        exit;
    }
}
