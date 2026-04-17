<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Auth;

use Application\Config\AuthRuntimeConfig;
use Application\Controllers\Auth\GoogleLoginController;
use Application\Services\Auth\GoogleAuthService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tests\Support\SessionIsolation;

class GoogleLoginControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use SessionIsolation;

    /** @var array<int, string> */
    private array $authFrontendEnvKeys = [
        'FRONTEND_DASHBOARD_URL',
        'FRONTEND_LOGIN_URL',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetSessionState();
        $this->clearAuthFrontendEnv();
        $_GET = [];
    }

    protected function tearDown(): void
    {
        $_GET = [];
        $this->clearAuthFrontendEnv();
        $this->resetSessionState();
        parent::tearDown();
    }

    public function testLoginReturnsRedirectResponseToGoogleAuthUrl(): void
    {
        $service = Mockery::mock(GoogleAuthService::class);
        $service->shouldReceive('getAuthUrl')->once()->andReturn('https://accounts.google.com/o/oauth2/auth');

        $controller = new GoogleLoginController($service);
        $response = $controller->login();

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('https://accounts.google.com/o/oauth2/auth', $response->getHeaders()['Location'] ?? null);
    }

    public function testLoginRedirectsAuthenticatedUserToConfiguredDashboard(): void
    {
        $this->startIsolatedSession('google-login-authenticated');
        $_SESSION['user_id'] = 1;
        $_SESSION['last_activity'] = time();
        $this->setEnvValue('FRONTEND_DASHBOARD_URL', 'https://app.example.com/dashboard');

        $controller = new GoogleLoginController(Mockery::mock(GoogleAuthService::class), new AuthRuntimeConfig());
        $response = $controller->login();

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('https://app.example.com/dashboard', $response->getHeaders()['Location'] ?? null);
    }

    public function testLoginRedirectsToConfiguredFrontendLoginWhenGoogleInitFails(): void
    {
        $service = Mockery::mock(GoogleAuthService::class);
        $service->shouldReceive('getAuthUrl')->once()->andThrow(new \Exception('oauth indisponivel'));
        $this->setEnvValue('FRONTEND_LOGIN_URL', 'https://app.example.com/login');

        $controller = new GoogleLoginController($service, new AuthRuntimeConfig());
        $response = $controller->login();

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('https://app.example.com/login', $response->getHeaders()['Location'] ?? null);
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
