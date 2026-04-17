<?php

declare(strict_types=1);

namespace Tests\Unit\Middlewares;

use Application\Config\AuthRuntimeConfig;
use Application\Core\Exceptions\HttpResponseException;
use Application\Core\Request;
use Application\Middlewares\AuthMiddleware;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tests\Support\SessionIsolation;

class AuthMiddlewareTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use SessionIsolation;

    /** @var array<int, string> */
    private array $authFrontendEnvKeys = [
        'FRONTEND_LOGIN_URL',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetSessionState();
        $this->clearAuthFrontendEnv();
        $_COOKIE = [];
        $_SERVER['REQUEST_URI'] = '/relatorios';
        $_SERVER['SCRIPT_NAME'] = '/index.php';
    }

    protected function tearDown(): void
    {
        $this->clearAuthFrontendEnv();
        unset($_COOKIE, $_SERVER['REQUEST_URI'], $_SERVER['SCRIPT_NAME']);
        $this->resetSessionState();
        parent::tearDown();
    }

    public function testHandleThrowsRedirectResponseForUnauthenticatedWebRequest(): void
    {
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('wantsJson')->andReturn(false);
        $request->shouldReceive('isAjax')->andReturn(false);

        try {
            AuthMiddleware::handle($request);
            $this->fail('Era esperado HttpResponseException.');
        } catch (HttpResponseException $e) {
            $response = $e->getResponse();

            $this->assertSame(302, $response->getStatusCode());
            $this->assertSame('http://localhost/lukrato/login?intended=relatorios', $response->getHeaders()['Location']);
        }
    }

    public function testHandleRedirectsToConfiguredFrontendLoginPreservingIntendedPath(): void
    {
        $this->setEnvValue('FRONTEND_LOGIN_URL', 'https://app.example.com/login');

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('wantsJson')->andReturn(false);
        $request->shouldReceive('isAjax')->andReturn(false);

        try {
            AuthMiddleware::handle($request);
            $this->fail('Era esperado HttpResponseException.');
        } catch (HttpResponseException $e) {
            $response = $e->getResponse();

            $this->assertSame(302, $response->getStatusCode());
            $this->assertSame('https://app.example.com/login?intended=relatorios', $response->getHeaders()['Location']);
        }
    }

    public function testHandleThrowsUnauthorizedResponseForKnownApiUser(): void
    {
        $_COOKIE['lukrato_known_user'] = '1';

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('wantsJson')->andReturn(true);
        $request->shouldReceive('isAjax')->andReturn(false);

        try {
            AuthMiddleware::handle($request);
            $this->fail('Era esperado HttpResponseException.');
        } catch (HttpResponseException $e) {
            $response = $e->getResponse();

            $this->assertSame(401, $response->getStatusCode());
            $this->assertSame([
                'success' => false,
                'message' => 'Sessão expirada',
            ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
        }
    }

    private function setEnvValue(string $key, string $value): void
    {
        $_ENV[$key] = $value;
        putenv("{$key}={$value}");
    }

    private function clearAuthFrontendEnv(): void
    {
        foreach ($this->authFrontendEnvKeys as $key) {
            unset($_ENV[$key]);
            putenv($key);
        }
    }
}
