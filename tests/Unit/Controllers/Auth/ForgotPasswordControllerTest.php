<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Auth;

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

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetSessionState();
        $this->startIsolatedSession('forgot-password-controller-test');
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    }

    protected function tearDown(): void
    {
        unset(
            $_POST,
            $_SERVER['REQUEST_METHOD'],
            $_SERVER['SCRIPT_NAME'],
            $_SERVER['HTTP_X_REQUESTED_WITH'],
            $_SERVER['REMOTE_ADDR']
        );
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
}
