<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use Application\Container\ApplicationContainer;
use Application\Core\Exceptions\AuthException;
use Application\Core\Exceptions\HttpResponseException;
use Application\Core\Request;
use Application\Core\Response;
use Application\Core\Router;
use Application\Core\Routing\MiddlewareResolver;
use Application\Services\Infrastructure\CacheService;
use Illuminate\Container\Container as IlluminateContainer;
use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        ApplicationContainer::flush();
        Router::reset();

        $_GET = [];
        $_POST = [];
        $_FILES = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_ACCEPT'] = 'application/json';
    }

    protected function tearDown(): void
    {
        ApplicationContainer::flush();
        unset($_SERVER['REQUEST_METHOD'], $_SERVER['HTTP_ACCEPT'], $_SERVER['REMOTE_ADDR']);
        $_GET = [];
        $_POST = [];
        $_FILES = [];

        Router::reset();

        parent::tearDown();
    }

    public function testRunReturnsResponseFromMatchedCallback(): void
    {
        $path = 'tests/router/' . bin2hex(random_bytes(6));

        Router::add('GET', $path, static function () {
            return Response::successResponse(['ok' => true], 'Tudo certo');
        });

        $response = Router::run($path, 'GET');

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'success' => true,
            'message' => 'Tudo certo',
            'data' => ['ok' => true],
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testRunMapsAuthExceptionCodeToForbiddenResponse(): void
    {
        $path = 'tests/router-auth/' . bin2hex(random_bytes(6));

        Router::add('GET', $path, static function () {
            throw new AuthException('Acesso negado', 403);
        });

        $response = Router::run($path, 'GET');
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(403, $response->getStatusCode());
        $this->assertFalse($payload['success']);
        $this->assertSame('Acesso negado', $payload['message']);
        $this->assertIsString($payload['request_id'] ?? null);
    }

    public function testRunReturnsResponseFromHttpResponseException(): void
    {
        $path = 'tests/router-response-exception/' . bin2hex(random_bytes(6));

        Router::add('GET', $path, static function () {
            throw new HttpResponseException(
                Response::redirectResponse('/login?intended=dashboard')
            );
        });

        $response = Router::run($path, 'GET');

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/login?intended=dashboard', $response->getHeaders()['Location']);
    }

    public function testRunReturnsJsonNotFoundResponseForApiRequest(): void
    {
        $response = Router::run('tests/route-missing/' . bin2hex(random_bytes(6)), 'GET');

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'Recurso não encontrado',
            'code' => 'RESOURCE_NOT_FOUND',
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testRunMatchesRootRoute(): void
    {
        Router::add('GET', '/', static function () {
            return Response::successResponse(['root' => true]);
        });

        $response = Router::run('/', 'GET');

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'success' => true,
            'message' => 'Success',
            'data' => ['root' => true],
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testRunEscapesStaticRouteSegments(): void
    {
        $path = '/tests/router/sitemap.xml/' . bin2hex(random_bytes(4));

        Router::add('GET', $path, static function () {
            return Response::successResponse(['matched' => true]);
        });

        $matched = Router::run($path, 'GET');
        $notMatched = Router::run(str_replace('.xml', 'Xxml', $path), 'GET');

        $this->assertSame(200, $matched?->getStatusCode());
        $this->assertSame(404, $notMatched?->getStatusCode());
    }

    public function testRunPassesRouteParamsToCallableWithoutOverwritingThemWithRequest(): void
    {
        $path = 'tests/router-slug/' . bin2hex(random_bytes(6));

        Router::add('GET', $path . '/{slug}', static function (string $slug) {
            return Response::successResponse(['slug' => $slug]);
        });

        $response = Router::run($path . '/meu-slug', 'GET');

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame([
            'success' => true,
            'message' => 'Success',
            'data' => ['slug' => 'meu-slug'],
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testRunInjectsRequestOnlyWhenCallableExplicitlyTypeHintsIt(): void
    {
        $path = 'tests/router-request/' . bin2hex(random_bytes(6));
        $_GET['token'] = 'abc123';

        Router::add('GET', $path . '/{slug}', static function (Request $request, string $slug) {
            return Response::successResponse([
                'slug' => $slug,
                'token' => $request->query('token'),
            ]);
        });

        $response = Router::run($path . '/segmento', 'GET');

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame([
            'success' => true,
            'message' => 'Success',
            'data' => [
                'slug' => 'segmento',
                'token' => 'abc123',
            ],
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testRunResolvesControllerThroughContainerWhenAvailable(): void
    {
        $this->registerControllerAlias(
            RouterContainerAwareController::class,
            'Application\\Controllers\\Test\\RouterContainerAwareController'
        );

        $container = new IlluminateContainer();
        $container->instance(
            RouterContainerDependency::class,
            new RouterContainerDependency('container-bound')
        );
        ApplicationContainer::setInstance($container);

        $_GET['token'] = 'bound-token';
        $path = 'tests/router-controller-container/' . bin2hex(random_bytes(6));

        Router::add('GET', $path, 'Test/RouterContainerAwareController@show');

        $response = Router::run($path, 'GET');

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame([
            'success' => true,
            'message' => 'Success',
            'data' => [
                'token' => 'bound-token',
                'source' => 'container-bound',
            ],
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testRunAutoResolvesControllerWithoutExplicitBinding(): void
    {
        $this->registerControllerAlias(
            RouterAutowireController::class,
            'Application\\Controllers\\Test\\RouterAutowireController'
        );

        $path = 'tests/router-controller-autowire/' . bin2hex(random_bytes(6));

        Router::add('GET', $path, 'Test/RouterAutowireController@show');

        $response = Router::run($path, 'GET');

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame([
            'success' => true,
            'message' => 'Success',
            'data' => [
                'autowired' => true,
            ],
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testRunReturnsMethodNotAllowedWithAllowHeader(): void
    {
        $path = 'tests/router-method/' . bin2hex(random_bytes(6));

        Router::add('GET', $path, static function () {
            return Response::successResponse();
        });
        Router::add('POST', $path, static function () {
            return Response::successResponse();
        });

        $response = Router::run($path, 'DELETE');

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(405, $response->getStatusCode());
        $this->assertSame('GET, POST', $response->getHeaders()['Allow'] ?? null);
        $this->assertSame([
            'success' => false,
            'message' => 'Metodo nao permitido',
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testRunInvokesInstanceMiddlewareUsingResolvedHandleArguments(): void
    {
        RouterInstanceMiddlewareStub::reset();
        $this->setMiddlewareRegistry(['test.instance' => RouterInstanceMiddlewareStub::class]);

        $_SERVER['REMOTE_ADDR'] = '203.0.113.42';
        $path = 'tests/router-middleware-instance/' . bin2hex(random_bytes(6));

        Router::add('GET', $path, static function () {
            return Response::successResponse(['ok' => true]);
        }, ['test.instance']);

        $response = Router::run($path, 'GET');

        $this->assertSame(200, $response?->getStatusCode());
        $this->assertTrue(RouterInstanceMiddlewareStub::$constructedWithCacheService);
        $this->assertSame([
            'method' => 'GET',
            'identifier' => 'custom:203.0.113.42',
            'endpoint' => 'global',
        ], RouterInstanceMiddlewareStub::$calls[0] ?? null);
    }

    public function testRunInvokesStaticMiddlewareUsingResolvedRequestArgument(): void
    {
        RouterStaticMiddlewareStub::reset();
        $this->setMiddlewareRegistry(['test.static' => RouterStaticMiddlewareStub::class]);

        $path = 'tests/router-middleware-static/' . bin2hex(random_bytes(6));

        Router::add('GET', $path, static function () {
            return Response::successResponse(['ok' => true]);
        }, ['test.static']);

        $response = Router::run($path, 'GET');

        $this->assertSame(200, $response?->getStatusCode());
        $this->assertSame(['method' => 'GET'], RouterStaticMiddlewareStub::$calls[0] ?? null);
    }

    private function setMiddlewareRegistry(array $registry): void
    {
        $property = new \ReflectionProperty(MiddlewareResolver::class, 'registry');
        $property->setAccessible(true);
        $property->setValue(null, $registry);
    }

    private function registerControllerAlias(string $sourceClass, string $alias): void
    {
        if (!class_exists($alias, false)) {
            class_alias($sourceClass, $alias);
        }
    }
}

final class RouterInstanceMiddlewareStub
{
    public static bool $constructedWithCacheService = false;

    /** @var array<int, array<string, string>> */
    public static array $calls = [];

    public function __construct(CacheService $cacheService, ?int $maxAttempts = null)
    {
        self::$constructedWithCacheService = $cacheService instanceof CacheService;
    }

    public function handle(Request $request, string $identifier, string $endpoint = 'global'): void
    {
        self::$calls[] = [
            'method' => $request->method(),
            'identifier' => $identifier,
            'endpoint' => $endpoint,
        ];
    }

    public static function getIdentifier(Request $request): string
    {
        return 'custom:' . $request->ip();
    }

    public static function reset(): void
    {
        self::$constructedWithCacheService = false;
        self::$calls = [];
    }
}

final class RouterStaticMiddlewareStub
{
    /** @var array<int, array<string, string>> */
    public static array $calls = [];

    public static function handle(Request $request): void
    {
        self::$calls[] = [
            'method' => $request->method(),
        ];
    }

    public static function reset(): void
    {
        self::$calls = [];
    }
}

final class RouterContainerDependency
{
    public function __construct(
        public readonly string $source
    ) {}
}

final class RouterContainerAwareController
{
    public function __construct(
        private readonly Request $request,
        private readonly RouterContainerDependency $dependency
    ) {}

    public function show(): Response
    {
        return Response::successResponse([
            'token' => $this->request->query('token'),
            'source' => $this->dependency->source,
        ]);
    }
}

final class RouterAutowireController
{
    public function show(): Response
    {
        return Response::successResponse([
            'autowired' => true,
        ]);
    }
}
