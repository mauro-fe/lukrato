<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api\Admin;

use Application\Controllers\Api\Admin\ConfigController;
use Application\Core\Exceptions\AuthException;
use Application\Models\Usuario;
use Application\Services\User\PerfilService;
use Application\Validators\PerfilValidator;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tests\Support\SessionIsolation;

class ConfigControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
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

    public function testUpdateReturnsErrorResponseWhenPayloadIsEmpty(): void
    {
        $this->seedAuthenticatedUserSession(111, 'Config User');

        $controller = new ConfigController(
            Mockery::mock(PerfilService::class),
            Mockery::mock(PerfilValidator::class),
        );

        $response = $controller->update();

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'Nenhum dado enviado.',
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testUpdateReturnsGoneResponseForUnsupportedLegacyPayload(): void
    {
        $this->seedAuthenticatedUserSession(112, 'Config Legacy');
        $_POST = ['foo' => 'bar'];

        $controller = new ConfigController(
            Mockery::mock(PerfilService::class),
            Mockery::mock(PerfilValidator::class),
        );

        $response = $controller->update();

        $this->assertSame(410, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'Rota legada de configurações não suporta mais este payload. Use /api/perfil ou /api/perfil/tema.',
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testUpdateDelegatesThemePayloadAndReturnsPerfilResponse(): void
    {
        $this->seedAuthenticatedUserSession(113, 'Config Theme');
        $_POST = ['theme' => 'light'];

        $controller = new ConfigController(
            Mockery::mock(PerfilService::class),
            Mockery::mock(PerfilValidator::class),
        );

        $response = $controller->update();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'success' => true,
            'message' => 'Success',
            'data' => [
                'message' => 'Tema atualizado com sucesso',
                'theme' => 'light',
            ],
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testUpdateThrowsAuthExceptionWhenSessionIsMissing(): void
    {
        $controller = new ConfigController(
            Mockery::mock(PerfilService::class),
            Mockery::mock(PerfilValidator::class),
        );

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Nao autenticado');

        $controller->update();
    }

    private function seedAuthenticatedUserSession(int $userId, string $name): void
    {
        $this->startIsolatedSession('config-controller-test');

        $user = new TestConfigUser();
        $user->id = $userId;
        $user->nome = $name;
        $user->is_admin = 1;
        $user->senha = password_hash('Senha@123', PASSWORD_DEFAULT);

        $_SESSION['usuario_logged_in'] = true;
        $_SESSION['user_id'] = $userId;
        $_SESSION['usuario_nome'] = $name;
        $_SESSION['usuario_cache'] = [
            'id' => $userId,
            'data' => $user,
        ];
    }
}

final class TestConfigUser extends Usuario
{
    public bool $saved = false;

    public function save(array $options = []): bool
    {
        $this->saved = true;

        return true;
    }
}
