<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Auth;

use Application\Config\AuthRuntimeConfig;
use Application\Controllers\Auth\ForgotPasswordController;
use Application\Core\Exceptions\ValidationException;
use Application\Middlewares\CsrfMiddleware;
use Application\Services\Auth\PasswordResetService;
use Application\Services\Infrastructure\CacheService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tests\Support\SessionIsolation;

class ForgotPasswordControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use SessionIsolation;

    /** @var array<int, string> */
    private array $authFrontendEnvKeys = [
        'FRONTEND_FORGOT_PASSWORD_URL',
        'FRONTEND_LOGIN_URL',
        'FRONTEND_RESET_PASSWORD_URL',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetSessionState();
        $this->startIsolatedSession('forgot-password-controller-test');
        $this->clearAuthFrontendEnv();
        $_GET = [];
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    }

    protected function tearDown(): void
    {
        unset(
            $_GET,
            $_POST,
            $_SERVER['REQUEST_METHOD'],
            $_SERVER['SCRIPT_NAME'],
            $_SERVER['HTTP_X_REQUESTED_WITH'],
            $_SERVER['REMOTE_ADDR']
        );
        $this->clearAuthFrontendEnv();
        $this->resetSessionState();
        parent::tearDown();
    }

    public function testSendResetLinkReturns429WhenRateLimitIsExceeded(): void
    {
        $_POST['csrf_token'] = CsrfMiddleware::generateToken('forgot_form');
        $_POST['email'] = 'user@example.com';

        $service = Mockery::mock(PasswordResetService::class);
        $service->shouldNotReceive('requestReset');

        $cache = Mockery::mock(CacheService::class);
        $cache
            ->shouldReceive('checkRateLimit')
            ->once()
            ->andThrow(new ValidationException(
                ['rate_limit' => 'Muitas tentativas. Aguarde 60 segundos e tente novamente.'],
                'Rate limit exceeded',
                429
            ));

        $controller = new ForgotPasswordController($service, $cache);

        $response = $controller->sendResetLink();
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(429, $response->getStatusCode());
        $this->assertFalse($payload['success']);
        $this->assertSame('Muitas tentativas. Aguarde 1 minuto e tente novamente.', $payload['message']);
    }

    public function testShowRequestFormRedirectsToConfiguredFrontendPage(): void
    {
        $this->setEnvValue('FRONTEND_FORGOT_PASSWORD_URL', 'https://app.example.com/forgot-password');

        $controller = new ForgotPasswordController(
            Mockery::mock(PasswordResetService::class),
            Mockery::mock(CacheService::class),
            new AuthRuntimeConfig()
        );

        $response = $controller->showRequestForm();

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('https://app.example.com/forgot-password', $response->getHeaders()['Location'] ?? null);
    }

    public function testShowResetFormRendersApiFirstShell(): void
    {
        $this->setEnvValue('FRONTEND_FORGOT_PASSWORD_URL', 'https://app.example.com/forgot-password');
        $this->setEnvValue('FRONTEND_LOGIN_URL', 'https://app.example.com/login');

        $service = Mockery::mock(PasswordResetService::class);
        $service->shouldNotReceive('getValidReset');

        $controller = new ForgotPasswordController($service, Mockery::mock(CacheService::class), new AuthRuntimeConfig());
        $response = $controller->showResetForm();
        $content = $response->getContent();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('data-reset-password-root', $content);
        $this->assertStringContainsString('api/v1/auth/password/reset/validate', $content);
        $this->assertStringContainsString('api/v1/auth/password/reset', $content);
        $this->assertStringContainsString('https://app.example.com/forgot-password', $content);
        $this->assertStringContainsString('https://app.example.com/login', $content);
    }

    public function testShowResetFormRedirectsToConfiguredFrontendResetPage(): void
    {
        $_GET['selector'] = 'selector';
        $_GET['validator'] = 'validator';
        $this->setEnvValue('FRONTEND_RESET_PASSWORD_URL', 'https://app.example.com/reset-password');

        $controller = new ForgotPasswordController(
            Mockery::mock(PasswordResetService::class),
            Mockery::mock(CacheService::class),
            new AuthRuntimeConfig()
        );

        $response = $controller->showResetForm();

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(
            'https://app.example.com/reset-password?selector=selector&validator=validator',
            $response->getHeaders()['Location'] ?? null
        );
    }

    public function testValidateResetLinkReturnsRedirectHintWhenTokenIsInvalid(): void
    {
        $_GET['selector'] = 'selector';
        $_GET['validator'] = 'validator';

        $service = Mockery::mock(PasswordResetService::class);
        $service
            ->shouldReceive('getValidReset')
            ->once()
            ->with('', 'selector', 'validator')
            ->andReturnNull();

        $this->setEnvValue('FRONTEND_FORGOT_PASSWORD_URL', 'https://app.example.com/forgot-password');

        $controller = new ForgotPasswordController($service, Mockery::mock(CacheService::class), new AuthRuntimeConfig());
        $response = $controller->validateResetLink();
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertFalse($payload['success']);
        $this->assertSame('Token inválido ou expirado.', $payload['message']);
        $this->assertSame('https://app.example.com/forgot-password', $payload['errors']['redirect'] ?? null);
    }

    public function testResetPasswordReturns429WhenRateLimitIsExceeded(): void
    {
        $_POST['csrf_token'] = CsrfMiddleware::generateToken('reset_form');
        $_POST['selector'] = 'selector';
        $_POST['validator'] = 'validator';
        $_POST['password'] = 'NovaSenha@123';
        $_POST['password_confirmation'] = 'NovaSenha@123';

        $service = Mockery::mock(PasswordResetService::class);
        $service->shouldNotReceive('resetPassword');

        $cache = Mockery::mock(CacheService::class);
        $cache
            ->shouldReceive('checkRateLimit')
            ->once()
            ->andThrow(new ValidationException(
                ['rate_limit' => 'Muitas tentativas. Aguarde 60 segundos e tente novamente.'],
                'Rate limit exceeded',
                429
            ));

        $controller = new ForgotPasswordController($service, $cache);

        $response = $controller->resetPassword();
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(429, $response->getStatusCode());
        $this->assertFalse($payload['success']);
        $this->assertSame('Muitas tentativas. Aguarde 1 minuto e tente novamente.', $payload['message']);
    }

    public function testResetPasswordReturnsConfiguredFrontendLoginRedirectWhenSuccessful(): void
    {
        $_POST['csrf_token'] = CsrfMiddleware::generateToken('reset_form');
        $_POST['selector'] = 'selector';
        $_POST['validator'] = 'validator';
        $_POST['password'] = 'NovaSenha@123';
        $_POST['password_confirmation'] = 'NovaSenha@123';

        $service = Mockery::mock(PasswordResetService::class);
        $service
            ->shouldReceive('resetPassword')
            ->once()
            ->with('', 'NovaSenha@123', 'NovaSenha@123', 'selector', 'validator');

        $cache = Mockery::mock(CacheService::class);
        $cache
            ->shouldReceive('checkRateLimit')
            ->once()
            ->with('password-reset-submit:127.0.0.1', 3, 60);

        $this->setEnvValue('FRONTEND_LOGIN_URL', 'https://app.example.com/login');

        $controller = new ForgotPasswordController($service, $cache, new AuthRuntimeConfig());
        $response = $controller->resetPassword();
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($payload['success']);
        $this->assertSame('Senha redefinida com sucesso! Faca login.', $payload['message']);
        $this->assertSame('https://app.example.com/login', $payload['data']['redirect'] ?? null);
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
