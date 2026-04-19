<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use Application\Core\Router;
use PHPUnit\Framework\TestCase;

class RouteIntegrityTest extends TestCase
{
    /** @var array<int, array{method:string,path:string,callback:mixed,middlewares:array<int,string>}> */
    private static array $routes = [];

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        Router::reset();
        self::loadAllRouteLoaders();
        self::$routes = self::registeredRoutes();
    }

    public static function tearDownAfterClass(): void
    {
        Router::reset();
        parent::tearDownAfterClass();
    }

    public function testRouteLoadersRegisterRoutes(): void
    {
        $this->assertNotEmpty(self::$routes, 'Nenhuma rota registrada ao carregar os loaders.');
    }

    public function testNoDuplicateMethodAndPathRoutes(): void
    {
        $seen = [];
        $duplicates = [];

        foreach (self::$routes as $route) {
            $key = $route['method'] . ' ' . $route['path'];

            if (isset($seen[$key])) {
                $duplicates[] = $key;
                continue;
            }

            $seen[$key] = true;
        }

        $this->assertSame(
            [],
            $duplicates,
            "Rotas duplicadas (metodo + path):\n" . implode("\n", array_unique($duplicates))
        );
    }

    public function testStringCallbacksResolveToExistingControllerMethods(): void
    {
        $invalidCallbacks = [];

        foreach (self::$routes as $route) {
            $callback = $route['callback'];

            if (!is_string($callback) || !str_contains($callback, '@')) {
                continue;
            }

            [$controllerPath, $method] = explode('@', $callback, 2);
            $controllerClass = 'Application\\Controllers\\' . str_replace('/', '\\', $controllerPath);

            if (!class_exists($controllerClass) || !method_exists($controllerClass, $method)) {
                $invalidCallbacks[] = sprintf(
                    '%s %s => %s',
                    $route['method'],
                    $route['path'],
                    $callback
                );
            }
        }

        $this->assertSame(
            [],
            $invalidCallbacks,
            "Callbacks de rotas inválidos:\n" . implode("\n", $invalidCallbacks)
        );
    }

    private static function loadAllRouteLoaders(): void
    {
        $routeFiles = [
            BASE_PATH . '/routes/web.php',
            BASE_PATH . '/routes/auth.php',
            BASE_PATH . '/routes/admin.php',
            BASE_PATH . '/routes/api.php',
            BASE_PATH . '/routes/webhooks.php',
        ];

        foreach ($routeFiles as $routeFile) {
            if (!file_exists($routeFile)) {
                throw new \RuntimeException("Route loader not found: {$routeFile}");
            }

            require $routeFile;
        }
    }

    /**
     * @return array<int, array{method:string,path:string,callback:mixed,middlewares:array<int,string>}>
     */
    private static function registeredRoutes(): array
    {
        $property = new \ReflectionProperty(Router::class, 'routes');
        $property->setAccessible(true);
        $routes = $property->getValue();

        return is_array($routes) ? $routes : [];
    }
}
