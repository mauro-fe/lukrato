<?php

declare(strict_types=1);

namespace Tests\Unit\Middlewares;

use Application\Config\RateLimitRuntimeConfig;
use Application\Container\ApplicationContainer;
use Application\Core\Exceptions\HttpResponseException;
use Application\Core\Exceptions\ValidationException;
use Application\Core\Request;
use Application\Middlewares\RateLimitMiddleware;
use Application\Services\Infrastructure\CacheService;
use Illuminate\Container\Container as IlluminateContainer;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class RateLimitMiddlewareTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();
        ApplicationContainer::flush();
        $_SERVER['REQUEST_URI'] = '/painel';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['SCRIPT_NAME'] = '/index.php';
    }

    protected function tearDown(): void
    {
        ApplicationContainer::flush();
        unset($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD'], $_SERVER['SCRIPT_NAME']);
        parent::tearDown();
    }

    public function testConstructorUsesRuntimeConfigDefaultsWhenOverridesAreNotProvided(): void
    {
        $cache = Mockery::mock(CacheService::class);

        $previousMaxAttempts = array_key_exists('RATELIMIT_MAX_ATTEMPTS', $_ENV) ? $_ENV['RATELIMIT_MAX_ATTEMPTS'] : null;
        $previousTimeWindow = array_key_exists('RATELIMIT_TIME_WINDOW', $_ENV) ? $_ENV['RATELIMIT_TIME_WINDOW'] : null;
        $previousProcessMaxAttempts = getenv('RATELIMIT_MAX_ATTEMPTS');
        $previousProcessTimeWindow = getenv('RATELIMIT_TIME_WINDOW');

        $_ENV['RATELIMIT_MAX_ATTEMPTS'] = '12';
        $_ENV['RATELIMIT_TIME_WINDOW'] = '90';
        putenv('RATELIMIT_MAX_ATTEMPTS=12');
        putenv('RATELIMIT_TIME_WINDOW=90');

        $runtimeConfig = new RateLimitRuntimeConfig();

        try {
            $container = new IlluminateContainer();
            $container->instance(RateLimitRuntimeConfig::class, $runtimeConfig);
            ApplicationContainer::setInstance($container);

            $middleware = new RateLimitMiddleware($cache);

            $this->assertSame($runtimeConfig, $this->readProperty($middleware, 'runtimeConfig'));
            $this->assertSame(12, $this->readProperty($middleware, 'maxAttempts'));
            $this->assertSame(90, $this->readProperty($middleware, 'timeWindow'));
        } finally {
            if ($previousMaxAttempts !== null) {
                $_ENV['RATELIMIT_MAX_ATTEMPTS'] = $previousMaxAttempts;
            } else {
                unset($_ENV['RATELIMIT_MAX_ATTEMPTS']);
            }

            if ($previousTimeWindow !== null) {
                $_ENV['RATELIMIT_TIME_WINDOW'] = $previousTimeWindow;
            } else {
                unset($_ENV['RATELIMIT_TIME_WINDOW']);
            }

            putenv(
                $previousProcessMaxAttempts !== false
                    ? 'RATELIMIT_MAX_ATTEMPTS=' . $previousProcessMaxAttempts
                    : 'RATELIMIT_MAX_ATTEMPTS'
            );
            putenv(
                $previousProcessTimeWindow !== false
                    ? 'RATELIMIT_TIME_WINDOW=' . $previousProcessTimeWindow
                    : 'RATELIMIT_TIME_WINDOW'
            );
        }
    }

    public function testHandleThrowsValidationExceptionForJsonRequests(): void
    {
        $cache = Mockery::mock(CacheService::class);
        $cache->shouldReceive('get')->once()->andReturn([time()]);
        $cache->shouldNotReceive('set');

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('wantsJson')->andReturn(true);
        $request->shouldReceive('isAjax')->andReturn(false);
        $request->shouldReceive('ip')->andReturn('127.0.0.1');

        $middleware = new RateLimitMiddleware($cache, 1, 60);

        $this->expectException(ValidationException::class);
        $this->expectExceptionCode(429);

        $middleware->handle($request, 'login:127.0.0.1');
    }

    public function testHandleThrowsHttpResponseExceptionForWebRequests(): void
    {
        $cache = Mockery::mock(CacheService::class);
        $cache->shouldReceive('get')->once()->andReturn([time()]);
        $cache->shouldNotReceive('set');

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('wantsJson')->andReturn(false);
        $request->shouldReceive('isAjax')->andReturn(false);
        $request->shouldReceive('ip')->andReturn('127.0.0.1');

        $middleware = new RateLimitMiddleware($cache, 1, 60);

        try {
            $middleware->handle($request, 'login:127.0.0.1');
            $this->fail('Era esperado HttpResponseException.');
        } catch (HttpResponseException $e) {
            $response = $e->getResponse();

            $this->assertSame(429, $response->getStatusCode());
            $this->assertSame('60', $response->getHeaders()['Retry-After']);
            $this->assertStringContainsString('429', $response->getContent());
        }
    }

    public function testHandleUsesAdminPolicyForAdministrativeRoutes(): void
    {
        $_SERVER['REQUEST_URI'] = '/api/sysadmin/users';

        $cache = Mockery::mock(CacheService::class);
        $cache->shouldReceive('get')->once()->andReturn(array_fill(0, 20, time()));
        $cache->shouldNotReceive('set');

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('wantsJson')->andReturn(true);
        $request->shouldReceive('isAjax')->andReturn(false);
        $request->shouldReceive('ip')->andReturn('127.0.0.1');

        $middleware = new RateLimitMiddleware($cache);

        $this->expectException(ValidationException::class);
        $this->expectExceptionCode(429);

        $middleware->handle($request, 'admin:127.0.0.1');
    }

    public function testHandleUsesAuthSensitivePolicyForPasswordResetRoutes(): void
    {
        $_SERVER['REQUEST_URI'] = '/recuperar-senha';

        $cache = Mockery::mock(CacheService::class);
        $cache->shouldReceive('get')->once()->andReturn(array_fill(0, 3, time()));
        $cache->shouldNotReceive('set');

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('wantsJson')->andReturn(true);
        $request->shouldReceive('isAjax')->andReturn(false);
        $request->shouldReceive('ip')->andReturn('127.0.0.1');

        $middleware = new RateLimitMiddleware($cache);

        $this->expectException(ValidationException::class);
        $this->expectExceptionCode(429);

        $middleware->handle($request, 'auth:127.0.0.1');
    }

    public function testHandleFailsClosedWhenAttemptStateCannotBePersisted(): void
    {
        $cache = Mockery::mock(CacheService::class);
        $cache->shouldReceive('get')->once()->andReturn([]);
        $cache->shouldReceive('set')->once()->andReturn(false);

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('wantsJson')->andReturn(true);
        $request->shouldReceive('isAjax')->andReturn(false);
        $request->shouldReceive('ip')->andReturn('127.0.0.1');

        $middleware = new RateLimitMiddleware($cache, 5, 60);

        $this->expectException(ValidationException::class);
        $this->expectExceptionCode(429);

        $middleware->handle($request, 'login:127.0.0.1');
    }

    private function readProperty(object $object, string $property): mixed
    {
        $reflection = new \ReflectionProperty($object, $property);
        $reflection->setAccessible(true);

        return $reflection->getValue($object);
    }
}
