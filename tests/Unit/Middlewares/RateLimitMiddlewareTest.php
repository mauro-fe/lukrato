<?php

declare(strict_types=1);

namespace Tests\Unit\Middlewares;

use Application\Core\Exceptions\HttpResponseException;
use Application\Core\Exceptions\ValidationException;
use Application\Core\Request;
use Application\Middlewares\RateLimitMiddleware;
use Application\Services\Infrastructure\CacheService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class RateLimitMiddlewareTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();
        $_SERVER['REQUEST_URI'] = '/painel';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['SCRIPT_NAME'] = '/index.php';
    }

    protected function tearDown(): void
    {
        unset($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD'], $_SERVER['SCRIPT_NAME']);
        parent::tearDown();
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
}
