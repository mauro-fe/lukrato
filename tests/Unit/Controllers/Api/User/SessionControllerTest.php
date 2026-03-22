<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api\User;

use Application\Controllers\Api\User\SessionController;
use Application\Models\Usuario;
use PHPUnit\Framework\TestCase;
use Tests\Support\SessionIsolation;

class SessionControllerTest extends TestCase
{
    use SessionIsolation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetSessionState();
        $_GET = [];
        $_POST = [];
        $_REQUEST = [];
    }

    protected function tearDown(): void
    {
        $_GET = [];
        $_POST = [];
        $_REQUEST = [];
        $this->resetSessionState();
        parent::tearDown();
    }

    public function testStatusReturnsUnauthorizedWhenSessionIsMissing(): void
    {
        $controller = new SessionController();

        $response = $controller->status();

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'Usuário não autenticado',
            'errors' => [
                'authenticated' => false,
                'expired' => true,
                'remainingTime' => 0,
                'showWarning' => false,
                'canRenew' => false,
            ],
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testStatusReturnsAuthenticatedPayloadWhenSessionIsActive(): void
    {
        $this->seedAuthenticatedSession(1001, 'Session User');

        $controller = new SessionController();

        $response = $controller->status();
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($payload['success']);
        $this->assertTrue($payload['data']['authenticated']);
        $this->assertFalse($payload['data']['expired']);
        $this->assertSame('Session User', $payload['data']['userName']);
        $this->assertGreaterThan(0, $payload['data']['remainingTime']);
    }

    public function testRenewReturnsUnauthorizedWhenSessionIsMissing(): void
    {
        $controller = new SessionController();

        $response = $controller->renew();

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'Sessão inválida. Por favor, faça login novamente.',
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testHeartbeatReturnsSuccessWhenUserIsAuthenticated(): void
    {
        $this->seedAuthenticatedSession(1002, 'Heartbeat User');

        $controller = new SessionController();

        $response = $controller->heartbeat();
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($payload['success']);
        $this->assertTrue($payload['data']['alive']);
        $this->assertGreaterThanOrEqual(0, $payload['data']['remainingTime']);
    }

    private function seedAuthenticatedSession(int $userId, string $name): void
    {
        $this->startIsolatedSession('session-controller-test');

        $user = new Usuario();
        $user->id = $userId;
        $user->nome = $name;
        $user->is_admin = 0;
        $user->senha = password_hash('Senha@123', PASSWORD_DEFAULT);

        $_SESSION['usuario_logged_in'] = true;
        $_SESSION['user_id'] = $userId;
        $_SESSION['usuario_nome'] = $name;
        $_SESSION['last_activity'] = time();
        $_SESSION['usuario_cache'] = [
            'id' => $userId,
            'data' => $user,
        ];
    }
}
