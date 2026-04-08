<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api\Configuracoes;

use Application\Controllers\Api\Configuracoes\PreferenciaUsuarioController;
use Application\Core\Exceptions\AuthException;
use Application\Models\Usuario;
use PHPUnit\Framework\TestCase;
use Tests\Support\SessionIsolation;

class PreferenciaUsuarioControllerTest extends TestCase
{
    use SessionIsolation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetSessionState();
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'POST';
    }

    protected function tearDown(): void
    {
        $_POST = [];
        unset($_SERVER['REQUEST_METHOD']);
        $this->resetSessionState();
        parent::tearDown();
    }

    public function testUpdateReturnsValidationErrorForInvalidTheme(): void
    {
        $this->seedAuthenticatedUserSession(41, 'Theme User');
        $_POST['theme'] = 'sepia';

        $controller = new PreferenciaUsuarioController();

        $response = $controller->update();

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => [
                'theme' => 'Deve ser: light, dark ou system.',
            ],
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testShowThrowsAuthExceptionWhenSessionIsMissing(): void
    {
        $controller = new PreferenciaUsuarioController();

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Não autenticado');

        $controller->show();
    }

    private function seedAuthenticatedUserSession(int $userId, string $name): void
    {
        $this->startIsolatedSession('preferencia-usuario-controller-test');

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
