<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api\AI;

use Application\Controllers\Api\AI\WhatsAppLinkController;
use Application\Core\Exceptions\AuthException;
use Application\Models\Usuario;
use PHPUnit\Framework\TestCase;
use Tests\Support\SessionIsolation;

class WhatsAppLinkControllerTest extends TestCase
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

    public function testRequestLinkReturnsValidationErrorForInvalidPhone(): void
    {
        $this->seedAuthenticatedUserSession(31, 'WhatsApp User');
        $_POST['phone'] = '123';

        $controller = new WhatsAppLinkController();

        $response = $controller->requestLink();

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'Número de telefone inválido. Use o formato: 5511999999999',
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testRequestLinkThrowsAuthExceptionWhenSessionIsMissing(): void
    {
        $controller = new WhatsAppLinkController();

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Nao autenticado');

        $controller->requestLink();
    }

    private function seedAuthenticatedUserSession(int $userId, string $name): void
    {
        $this->startIsolatedSession('whatsapp-link-controller-test');

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
