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

    public function testStoreLogsInAndReturnsDashboardRedirectForGoogleRegistration(): void
    {
        $_POST['csrf_token'] = CsrfMiddleware::generateToken('register_form');
        $_POST['name'] = 'Teste Google';
        $_POST['email'] = 'google@lukrato.com';
        $_SESSION['social_register'] = [
            'provider' => 'google',
            'google_id' => 'gid-123',
        ];

        $authService = Mockery::mock(AuthService::class);
        $authService
            ->shouldReceive('register')
            ->once()
            ->with(Mockery::on(static function (array $payload): bool {
                return ($payload['provider'] ?? null) === 'google'
                    && ($payload['google_id'] ?? null) === 'gid-123'
                    && ($payload['password'] ?? null) === null
                    && ($payload['password_confirmation'] ?? null) === null
                    && ($payload['email'] ?? null) === 'google@lukrato.com';
            }))
            ->andReturn([
                'user_id' => 55,
                'redirect' => 'dashboard',
            ]);

        $googleAuthService = Mockery::mock(GoogleAuthService::class);
        $googleAuthService
            ->shouldReceive('loginAfterRegistration')
            ->once()
            ->with(55, 'google@lukrato.com')
            ->andReturnTrue();

        $controller = new RegistroController($authService, $googleAuthService);

        $response = $controller->store();
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertTrue($payload['success']);
        $this->assertSame('Conta criada com Google e login realizado com sucesso!', $payload['message']);
        $this->assertSame([
            'redirect' => 'dashboard',
            'requires_verification' => false,
        ], $payload['data']);
        $this->assertArrayNotHasKey('social_register', $_SESSION);
    }
}
