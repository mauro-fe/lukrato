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
            'method' => strtoupper($method),
            'path' => trim($path, '/'),
            'callback' => $callback,
            'middlewares' => $middlewares,
        ];
    }

    /**
     * Ponto de entrada do roteador. Orquestra a busca, validação e execução da rota.
     */
    public static function run(string $requestedPath, string $requestMethod): void
    {
        $routeContext = null; // Para guardar o contexto da rota em caso de erro
        try {
            $request = new Request();
            $routeData = self::findMatchingRoute($requestedPath, $requestMethod);

            if ($routeData === null) {
                self::handleNotFound();
                return;
            }

            $routeContext = $routeData['route'];

            self::executeMiddlewares($routeContext['middlewares'], $request);
            self::executeCallback($routeContext, $routeData['params'], $request);
        } catch (AuthException | ValidationException $e) {
            self::handleAuthOrValidationException($e);
        } catch (\Throwable $e) {
            self::handleException($e, $routeContext);
        }
    }

    /**
     * Encontra a rota correspondente à requisição.
     * @return array|null Retorna os dados da rota e os parâmetros da URL, ou nulo.
     */
    private static function findMatchingRoute(string $path, string $method): ?array
    {
        $path = $path === '' ? '/' : trim($path, '/');

        foreach (self::$routes as $route) {
            $pattern = "#^" . preg_replace('/\{[a-zA-Z0-9_]+\}/', '([a-zA-Z0-9_-]+)', $route['path']) . "$#";

            if ($method === $route['method'] && preg_match($pattern, $path, $matches)) {
                array_shift($matches);
                return ['route' => $route, 'params' => $matches];
            }
        }
        return null;
    }

    /**
     * Executa os middlewares associados a uma rota.
     */
    private static function executeMiddlewares(array $middlewareNames, Request $request): void
    {
        if (empty($middlewareNames)) return;

        $registry = require BASE_PATH . '/Application/Middlewares/RegistryMiddleware.php';

        foreach ($middlewareNames as $name) {
            if (!isset($registry[$name])) {
                throw new \Exception("Middleware '{$name}' não está registrado.");
            }
            $middlewareClass = $registry[$name];

            // Lógica para instanciar middlewares com dependências
            if ($name === 'ratelimit') {
                (new $middlewareClass(new CacheService()))->handle($request);
            } else {
                $middlewareClass::handle($request); // Para middlewares estáticos
            }
        }
    }

    /**
     * Executa o callback da rota (Controller ou Closure).
     */
    private static function executeCallback(array $route, array $params, Request $request): void
    {
        if (is_callable($route['callback'])) {
            array_unshift($params, $request);
            call_user_func_array($route['callback'], $params);
            return;
        }

        [$controller, $method] = explode('@', $route['callback']);
        $controllerNs = 'Application\\Controllers\\' . str_replace('/', '\\', $controller);

        if (!class_exists($controllerNs) || !method_exists($controllerNs, $method)) {
            throw new \Exception("Callback da rota inválido: {$route['callback']}");
        }

        $instance = new $controllerNs();
        call_user_func_array([$instance, $method], $params);
    }

    /**
     * Lida com exceções de autenticação e validação.
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
            echo json_encode(['status' => 'error', 'message' => $e->getMessage(), 'errors' => $e->getErrors()]);
            exit;
        }
    }

    /**
     * Lida com exceções genéricas, mostrando um erro detalhado em dev e um erro 500 em prod.
     */
    public static function handleException(\Throwable $e, ?array $routeContext): void
    {
        LogService::critical('Erro fatal no Router', ['erro' => $e->getMessage(), 'rota' => $routeContext]);

        if (($_ENV['APP_ENV'] ?? 'production') === 'development') {
            http_response_code(500);
            echo '<h1>Erro na Aplicação</h1><pre>';
            echo '<strong>Mensagem:</strong> ' . htmlspecialchars($e->getMessage()) . "\n\n";
            echo '<strong>Arquivo:</strong> ' . $e->getFile() . ' (Linha ' . $e->getLine() . ")\n\n";
            echo '<strong>Trace:</strong>' . "\n" . htmlspecialchars($e->getTraceAsString());
            echo '</pre>';
        } else {
            http_response_code(500);
            include BASE_PATH . '/views/errors/500.php';
        }
        exit;
    }

    /**
     * Lida com rotas não encontradas (404).
     */
    private static function handleNotFound(): void
    {
        http_response_code(404);
        include BASE_PATH . '/views/errors/404.php';
    }
}
