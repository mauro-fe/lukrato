<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Auth;

use Application\Controllers\Auth\RegistroController;
use Application\Middlewares\CsrfMiddleware;
use Application\Services\Auth\AuthService;
use Application\Services\Auth\GoogleAuthService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tests\Support\SessionIsolation;

class RegistroControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use SessionIsolation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetSessionState();
        $this->startIsolatedSession('registro-controller-test');

        $_POST = [];
        $_SESSION = [];
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

    public function testStoreReturnsSpecificAntifraudMessageForAjax(): void
    {
        $_POST['csrf_token'] = CsrfMiddleware::generateToken('register_form');
        $_POST['name'] = 'Teste Lukrato';
        $_POST['email'] = 'lukratosistema@gmail.com';
        $_POST['password'] = 'Senha@123';
        $_POST['password_confirmation'] = 'Senha@123';

        $authService = Mockery::mock(AuthService::class);
        $authService
            ->shouldReceive('register')
            ->once()
            ->andThrow(new \InvalidArgumentException(
                'Limite de novas contas atingido para sua rede. Tente novamente mais tarde.'
            ));

        $googleAuthService = Mockery::mock(GoogleAuthService::class);
        $googleAuthService->shouldNotReceive('loginAfterRegistration');

        $controller = new RegistroController($authService, $googleAuthService);

        $response = $controller->store();
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(429, $response->getStatusCode());
        $this->assertFalse($payload['success']);
        $this->assertSame(
            'Limite de novas contas atingido para sua rede. Tente novamente mais tarde.',
            $payload['message']
        );
    }

    public function testStoreKeepsGenericMessageForUnexpectedErrors(): void
    {
        $_POST['csrf_token'] = CsrfMiddleware::generateToken('register_form');
        $_POST['name'] = 'Teste Lukrato';
        $_POST['email'] = 'lukratosistema@gmail.com';
        $_POST['password'] = 'Senha@123';
        $_POST['password_confirmation'] = 'Senha@123';

        $authService = Mockery::mock(AuthService::class);
        $authService
            ->shouldReceive('register')
            ->once()
            ->andThrow(new \RuntimeException('Falha interna no banco'));

        $googleAuthService = Mockery::mock(GoogleAuthService::class);
        $googleAuthService->shouldNotReceive('loginAfterRegistration');

        $controller = new RegistroController($authService, $googleAuthService);

        $response = $controller->store();
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertFalse($payload['success']);
        $this->assertSame('Falha ao cadastrar. Tente novamente mais tarde.', $payload['message']);
    }
}
