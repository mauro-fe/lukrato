<?php

namespace Application\Core;

// Importa as classes de middleware e exceções que serão usadas

use Application\Middlewares\RateLimitMiddleware;
use Application\Services\CacheService;
use Application\Services\LogService;
use Application\Core\Exceptions\AuthException; // Sua exceção de autenticação
use Application\Core\Exceptions\ValidationException;

use Application\Core\Request; // Para passar a instância de Request para o middleware

class Router
{
    private static array $routes = [];

    /**
     * @var array Lista de middlewares pré-instanciados ou configurados, se necessário.
     * Por enquanto, continuaremos usando estáticos para simplicidade,
     * mas este array serviria para injeção de dependência futura.
     */
    private static array $configuredMiddlewares = [];

    /**
     * Método para configurar/registrar middlewares, caso eles não sejam estáticos e precisem de dependências.
     * @param string $key A chave do middleware (ex: 'auth', 'csrf')
     * @param object $middlewareInstance A instância do middleware ou um callable que a retorna.
     */
    public static function setMiddleware(string $key, $middlewareInstance): void
    {
        self::$configuredMiddlewares[$key] = $middlewareInstance;
    }

    public static function add(string $method, string $path, $callback, array $middlewares = []): void
    {
        $path = trim($path, '/');
        self::$routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'callback' => $callback,
            'middlewares' => $middlewares, // Agora aceita middlewares como nomes curtos ou FQCN
        ];
    }

    public static function run(string $requestedPath, string $requestMethod): void
    {
        $requestedPath = trim($requestedPath, '/');
        $requestedPath = $requestedPath === '' ? '/' : $requestedPath;

        // Cria uma instância de Request para passar para os middlewares e controladores
        $request = new Request(); // Instancia a Request aqui e passa para os middlewares/controladores se precisar

        $cacheService = new CacheService();
        $rateLimitMiddleware = new RateLimitMiddleware($cacheService);

        foreach (self::$routes as $route) {
            $pattern = preg_replace('/\{[a-zA-Z0-9_]+\}/', '([a-zA-Z0-9_-]+)', $route['path']);
            $pattern = "#^" . trim($pattern, '/') . "$#";

            if ($requestMethod === $route['method'] && preg_match($pattern, $requestedPath, $matches)) {
                array_shift($matches); // Remove a correspondência completa da URL

                // --- EXECUÇÃO DOS MIDDLEWARES ---
                // Carrega o registro de middlewares uma vez
                $registry = require BASE_PATH . '/Application/Middlewares/RegistryMiddleware.php';

                // --- EXECUÇÃO DOS MIDDLEWARES ---
                try {
                    foreach ($route['middlewares'] as $middlewareName) {
                        if (!isset($registry[$middlewareName])) {
                            throw new \Exception("Middleware '{$middlewareName}' não registrado em RegistryMiddleware.php.");
                        }

                        $middlewareClass = $registry[$middlewareName];

                        if (!class_exists($middlewareClass) || !method_exists($middlewareClass, 'handle')) {
                            throw new \Exception("Middleware inválido: {$middlewareClass}");
                        }

                        // CSRF só é verificado em métodos perigosos
                        if ($middlewareName === 'csrf' && !in_array($requestMethod, ['POST', 'PUT', 'DELETE'])) {
                            continue;
                        }

                        // RateLimit pode precisar de dependência (exemplo: CacheService)
                        if ($middlewareName === 'ratelimit') {
                            $identifier = $middlewareClass::getIdentifier($request);
                            $instance = new $middlewareClass(new CacheService());
                            $instance->handle($request, $identifier);
                        } else {
                            // Execução estática simples
                            $middlewareClass::handle($request);
                        }
                    }
                } catch (AuthException $e) {
                    header('Location: ' . BASE_URL . 'admin/login');
                    exit;
                } catch (ValidationException $e) {
                    http_response_code($e->getCode() ?: 403);
                    echo json_encode(['status' => 'error', 'message' => $e->getMessage(), 'errors' => $e->getErrors()]);
                    exit;
                } catch (\Throwable $e) {
                    if (($_ENV['APP_ENV'] ?? 'production') === 'development') {
                        echo '<h1>Erro no Middleware</h1>';
                        echo '<pre>' . $e->getMessage() . '</pre>';
                        echo '<h2>Stack Trace:</h2>';
                        echo '<pre>' . $e->getTraceAsString() . '</pre>';
                    } else {
                        error_log("Middleware Error: {$e->getMessage()} in {$e->getFile()} line {$e->getLine()}");
                        http_response_code(500);
                        include BASE_PATH . '/views/errors/500.php';
                    }
                    exit;
                }


                // --- EXECUÇÃO DO CALLBACK DA ROTA ---
                if (is_callable($route['callback'])) {
                    // Passa a instância de Request como o primeiro argumento para a closure
                    array_unshift($matches, $request); // Adiciona $request no início dos parâmetros
                    call_user_func_array($route['callback'], $matches);
                    return;
                }

                // Suporte a controller@metodo
                if (is_string($route['callback'])) {
                    [$controllerPath, $method] = explode('@', $route['callback']);
                    $controllerNamespace = 'Application\\Controllers\\' . str_replace('/', '\\', $controllerPath);

                    try {
                        if (!class_exists($controllerNamespace)) {
                            LogService::error("Controlador não encontrado", [
                                'controller' => $controllerNamespace,
                                'rota' => $route['path'],
                                'callback' => $route['callback']
                            ]);
                            throw new \Exception("Controlador '{$controllerNamespace}' não encontrado.");
                        }

                        $controllerInstance = new $controllerNamespace();

                        if (!method_exists($controllerInstance, $method)) {
                            LogService::error("Método não encontrado no controlador", [
                                'controller' => $controllerNamespace,
                                'metodo' => $method,
                                'rota' => $route['path'],
                                'callback' => $route['callback']
                            ]);
                            throw new \Exception("Método '{$method}' não encontrado no controlador '{$controllerNamespace}'.");
                        }

                        call_user_func_array([$controllerInstance, $method], $matches);
                        return;
                    } catch (\Throwable $e) {
                        // 🔎 Durante DEV mostra erro completo
                        if (($_ENV['APP_ENV'] ?? 'production') === 'development') {
                            echo '<h1>Erro no Controller</h1>';
                            echo '<pre>' . $e->getMessage() . '</pre>';
                            echo '<h2>Stack Trace:</h2>';
                            echo '<pre>' . $e->getTraceAsString() . '</pre>';
                        } else {
                            // 🧾 Log para produção
                            LogService::critical('Erro interno no Router', [
                                'erro' => $e->getMessage(),
                                'rota' => $route['path'],
                                'callback' => $route['callback'],
                                'trace' => $e->getTraceAsString()
                            ]);
                            http_response_code(500);
                            include BASE_PATH . '/views/errors/500.php';
                        }
                        exit;
                    }
                }
            }
        }

        // Se nenhuma rota for encontrada, exibe 404
        http_response_code(404);
        $errorPage = BASE_PATH . '/views/errors/404.php';
        if (file_exists($errorPage)) {
            include $errorPage;
        } else {
            echo "<h2>Página não encontrada</h2>";
        }
    }
}