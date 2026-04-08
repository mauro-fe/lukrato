<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api\User;

use Application\Controllers\Api\User\SupportController;
use Application\Core\Exceptions\AuthException;
use Application\Models\Usuario;
use PHPUnit\Framework\TestCase;
use Tests\Support\SessionIsolation;

class SupportControllerTest extends TestCase
{
    use SessionIsolation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetSessionState();
        $_POST = [];
    }

    protected function tearDown(): void
    {
        $_POST = [];
        $this->resetSessionState();
        parent::tearDown();
    }

    public function testSendReturnsJsonErrorWhenMessageIsTooShort(): void
    {
        $this->seedAuthenticatedUserSession(51, 'Support User');
        $_POST['message'] = 'curta';

        $controller = new SupportController();

        $response = $controller->send();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'Mensagem é obrigatória e deve ter pelo menos 10 caracteres.',
            'errors' => [
                'message' => 'Mensagem é obrigatória e deve ter pelo menos 10 caracteres.',
            ],
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testSendThrowsAuthExceptionWhenSessionIsMissing(): void
    {
        $controller = new SupportController();

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Não autenticado');

        $controller->send();
    }

    private function seedAuthenticatedUserSession(int $userId, string $name): void
    {
        $this->startIsolatedSession('support-controller-test');

        $user = new Usuario();
        $user->id = $userId;
        $user->nome = $name;
        $user->is_admin = 0;

        $_SESSION['usuario_logged_in'] = true;
        $_SESSION['user_id'] = $userId;
        $_SESSION['usuario_nome'] = $name;
        $_SESSION['usuario_cache'] = [
            'id' => $userId,
            'data' => $user,
        ];
    }
}
