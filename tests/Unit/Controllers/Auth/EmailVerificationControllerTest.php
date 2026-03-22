<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Auth;

use Application\Controllers\Auth\EmailVerificationController;
use Application\Core\Exceptions\ValidationException;
use Application\Middlewares\CsrfMiddleware;
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

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetSessionState();
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
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['SCRIPT_NAME']
        );
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

        $controller = new EmailVerificationController(Mockery::mock(EmailVerificationService::class));

        $response = $controller->notice();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('email@lukrato.com', $response->getContent());
        $this->assertStringContainsString('verifique seu email', strtolower($response->getContent()));
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
}
