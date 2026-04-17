<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Auth;

use Application\Config\AuthRuntimeConfig;
use Application\Controllers\Auth\EmailVerificationController;
use Application\Core\Exceptions\ValidationException;
use Application\Middlewares\CsrfMiddleware;
use Application\Repositories\UsuarioRepository;
use Application\Services\Auth\EmailVerificationService;
use Application\Services\Infrastructure\CacheService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tests\Support\SessionIsolation;

class EmailVerificationControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use SessionIsolation;

    /** @var array<int, string> */
    private array $authFrontendEnvKeys = [
        'FRONTEND_LOGIN_URL',
        'FRONTEND_VERIFY_EMAIL_NOTICE_URL',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetSessionState();
        $this->clearAuthFrontendEnv();
        $_GET = [];
        $_POST = [];
        $_SERVER['SCRIPT_NAME'] = '/index.php';
    }

    protected function tearDown(): void
    {
        $_GET = [];
        $_POST = [];
        unset(
            $_SERVER['REQUEST_METHOD'],
            $_SERVER['HTTP_X_REQUESTED_WITH'],
            $_SERVER['HTTP_ACCEPT'],
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['SCRIPT_NAME']
        );
        $this->clearAuthFrontendEnv();
        $this->resetSessionState();
        parent::tearDown();
    }

    public function testVerifyReturnsRedirectResponseAndStoresSuccessMessage(): void
    {
        $this->startIsolatedSession('email-verification-controller-test');
        $_GET['selector'] = 'abc123selector';
        $_GET['validator'] = 'abc123validator';

        $service = Mockery::mock(EmailVerificationService::class);
        $service
            ->shouldReceive('verifyEmail')
            ->once()
            ->with('', 'abc123selector', 'abc123validator')
            ->andReturn([
                'success' => true,
                'message' => 'Email verificado com sucesso.',
            ]);

        $controller = new EmailVerificationController($service);

        $response = $controller->verify();

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(BASE_URL . 'login', $response->getHeaders()['Location'] ?? null);
        $this->assertSame('Email verificado com sucesso.', $_SESSION['success'] ?? null);
    }

    public function testVerifyReturnsJsonPayloadForApiConsumers(): void
    {
        $_SERVER['HTTP_ACCEPT'] = 'application/json';
        $_GET['selector'] = 'abc123selector';
        $_GET['validator'] = 'abc123validator';

        $service = Mockery::mock(EmailVerificationService::class);
        $service
            ->shouldReceive('verifyEmail')
            ->once()
            ->with('', 'abc123selector', 'abc123validator')
            ->andReturn([
                'success' => true,
                'message' => 'Email verificado com sucesso.',
            ]);

        $this->setEnvValue('FRONTEND_LOGIN_URL', 'https://app.example.com/login');

        $controller = new EmailVerificationController(
            $service,
            Mockery::mock(CacheService::class),
            Mockery::mock(UsuarioRepository::class),
            new AuthRuntimeConfig()
        );

        $response = $controller->verify();
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($payload['success']);
        $this->assertSame('https://app.example.com/login', $payload['data']['redirect'] ?? null);
        $this->assertArrayNotHasKey('success', $_SESSION);
    }

    public function testVerifyStoresExpiredFlagsOnFailure(): void
    {
        $this->startIsolatedSession('email-verification-controller-test');
        $_GET['selector'] = 'expired-selector';
        $_GET['validator'] = 'expired-validator';

        $service = Mockery::mock(EmailVerificationService::class);
        $service
            ->shouldReceive('verifyEmail')
            ->once()
            ->with('', 'expired-selector', 'expired-validator')
            ->andReturn([
                'success' => false,
                'message' => 'Token expirado.',
                'expired' => true,
                'user_id' => 999,
            ]);

        $controller = new EmailVerificationController($service);

        $response = $controller->verify();

        $this->assertSame(302, $response->getStatusCode());
        $this->assertTrue($_SESSION['verification_expired'] ?? false);
        $this->assertSame(999, $_SESSION['verification_user_id'] ?? null);
        $this->assertSame('Token expirado.', $_SESSION['error'] ?? null);
    }

    public function testVerifyReturnsGonePayloadForExpiredApiRequests(): void
    {
        $this->startIsolatedSession('email-verification-expired-api');
        $_SERVER['HTTP_ACCEPT'] = 'application/json';
        $_GET['selector'] = 'expired-selector';
        $_GET['validator'] = 'expired-validator';

        $service = Mockery::mock(EmailVerificationService::class);
        $service
            ->shouldReceive('verifyEmail')
            ->once()
            ->with('', 'expired-selector', 'expired-validator')
            ->andReturn([
                'success' => false,
                'message' => 'Token expirado.',
                'expired' => true,
                'user_id' => 999,
            ]);

        $this->setEnvValue('FRONTEND_LOGIN_URL', 'https://app.example.com/login');

        $controller = new EmailVerificationController(
            $service,
            Mockery::mock(CacheService::class),
            Mockery::mock(UsuarioRepository::class),
            new AuthRuntimeConfig()
        );

        $response = $controller->verify();
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(410, $response->getStatusCode());
        $this->assertFalse($payload['success']);
        $this->assertSame('Token expirado.', $payload['message']);
        $this->assertSame('https://app.example.com/login', $payload['errors']['redirect'] ?? null);
        $this->assertTrue($payload['errors']['expired'] ?? false);
        $this->assertSame(999, $payload['errors']['user_id'] ?? null);
        $this->assertTrue($_SESSION['verification_expired'] ?? false);
        $this->assertSame(999, $_SESSION['verification_user_id'] ?? null);
    }

    public function testNoticeReturnsRedirectResponseWhenSessionHasNoEmail(): void
    {
        $controller = new EmailVerificationController(Mockery::mock(EmailVerificationService::class));

        $response = $controller->notice();

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(BASE_URL . 'login', $response->getHeaders()['Location'] ?? null);
    }

    public function testNoticeReturnsHtmlResponseWhenSessionHasEmail(): void
    {
        $this->startIsolatedSession('email-verification-controller-test');
        $_SESSION['unverified_email'] = 'email@lukrato.com';
        $this->setEnvValue('FRONTEND_LOGIN_URL', 'https://app.example.com/login');

        $controller = new EmailVerificationController(
            Mockery::mock(EmailVerificationService::class),
            Mockery::mock(CacheService::class),
            Mockery::mock(UsuarioRepository::class),
            new AuthRuntimeConfig()
        );

        $response = $controller->notice();
        $content = $response->getContent();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('data-verify-email-root', $content);
        $this->assertStringContainsString('/api/v1/auth/email/verify', $content);
        $this->assertStringContainsString('/api/v1/auth/email/notice', $content);
        $this->assertStringContainsString('https://app.example.com/login', $content);
        $this->assertStringNotContainsString('email@lukrato.com', $content);
        $this->assertStringContainsString('Carregando seu aviso de verificacao', $content);
    }

    public function testNoticeReturnsHtmlResponseWhenRequestHasVerificationCredentials(): void
    {
        $_GET['selector'] = 'verify-selector';
        $_GET['validator'] = 'verify-validator';

        $controller = new EmailVerificationController(
            Mockery::mock(EmailVerificationService::class),
            Mockery::mock(CacheService::class),
            Mockery::mock(UsuarioRepository::class),
            new AuthRuntimeConfig()
        );

        $response = $controller->notice();
        $content = $response->getContent();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('data-verify-email-root', $content);
        $this->assertStringContainsString('/api/v1/auth/email/verify', $content);
    }

    public function testNoticeRedirectsToConfiguredFrontendPage(): void
    {
        $this->startIsolatedSession('email-verification-notice-redirect');
        $_SESSION['unverified_email'] = 'email@lukrato.com';
        $this->setEnvValue('FRONTEND_VERIFY_EMAIL_NOTICE_URL', 'https://app.example.com/verify-email');

        $controller = new EmailVerificationController(
            Mockery::mock(EmailVerificationService::class),
            Mockery::mock(CacheService::class),
            Mockery::mock(UsuarioRepository::class),
            new AuthRuntimeConfig()
        );

        $response = $controller->notice();

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('https://app.example.com/verify-email', $response->getHeaders()['Location'] ?? null);
    }

    public function testNoticeRedirectsToConfiguredFrontendPageWithVerificationCredentials(): void
    {
        $_GET['selector'] = 'verify-selector';
        $_GET['validator'] = 'verify-validator';
        $this->setEnvValue('FRONTEND_VERIFY_EMAIL_NOTICE_URL', 'https://app.example.com/verify-email');

        $controller = new EmailVerificationController(
            Mockery::mock(EmailVerificationService::class),
            Mockery::mock(CacheService::class),
            Mockery::mock(UsuarioRepository::class),
            new AuthRuntimeConfig()
        );

        $response = $controller->notice();

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(
            'https://app.example.com/verify-email?selector=verify-selector&validator=verify-validator',
            $response->getHeaders()['Location'] ?? null
        );
    }

    public function testNoticeDataReturnsSessionBackedPayload(): void
    {
        $this->startIsolatedSession('email-verification-notice-data');
        $_SESSION['unverified_email'] = 'email@lukrato.com';
        $this->setEnvValue('FRONTEND_LOGIN_URL', 'https://app.example.com/login');

        $controller = new EmailVerificationController(
            Mockery::mock(EmailVerificationService::class),
            Mockery::mock(CacheService::class),
            Mockery::mock(UsuarioRepository::class),
            new AuthRuntimeConfig()
        );

        $response = $controller->noticeData();
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($payload['success']);
        $this->assertSame('email@lukrato.com', $payload['data']['notice']['email'] ?? null);
        $this->assertFalse($payload['data']['notice']['expired'] ?? true);
        $this->assertSame('https://app.example.com/login', $payload['data']['actions']['login_url'] ?? null);
    }

    public function testNoticeDataReturnsExpiredPayloadWithoutEmail(): void
    {
        $this->startIsolatedSession('email-verification-expired-notice-data');
        $_SESSION['verification_expired'] = true;
        $_SESSION['verification_user_id'] = 42;
        $this->setEnvValue('FRONTEND_LOGIN_URL', 'https://app.example.com/login');

        $controller = new EmailVerificationController(
            Mockery::mock(EmailVerificationService::class),
            Mockery::mock(CacheService::class),
            Mockery::mock(UsuarioRepository::class),
            new AuthRuntimeConfig()
        );

        $response = $controller->noticeData();
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($payload['success']);
        $this->assertSame('', $payload['data']['notice']['email'] ?? null);
        $this->assertTrue($payload['data']['notice']['expired'] ?? false);
        $this->assertStringContainsString('expirou', $payload['data']['notice']['message'] ?? '');
    }

    public function testResendReturns429WhenRateLimitIsExceededForAjax(): void
    {
        $this->startIsolatedSession('email-verification-controller-test');
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_POST['csrf_token'] = CsrfMiddleware::generateToken('verify_email_form');
        $_POST['email'] = 'email@lukrato.com';

        $service = Mockery::mock(EmailVerificationService::class);
        $service->shouldNotReceive('resendVerificationEmail');

        $cache = Mockery::mock(CacheService::class);
        $cache
            ->shouldReceive('checkRateLimit')
            ->once()
            ->with('email-verification-resend:127.0.0.1', 3, 60)
            ->andThrow(new ValidationException(
                ['rate_limit' => 'Muitas tentativas. Aguarde 60 segundos e tente novamente.'],
                'Rate limit exceeded',
                429
            ));

        $controller = new EmailVerificationController($service, $cache);

        $response = $controller->resend();
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(429, $response->getStatusCode());
        $this->assertFalse($payload['success']);
        $this->assertSame('Muitas tentativas. Aguarde 1 minuto e tente novamente.', $payload['message']);
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
