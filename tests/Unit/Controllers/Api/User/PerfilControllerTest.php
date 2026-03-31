<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api\User;

use Application\Controllers\Api\User\PerfilController;
use Application\Core\Exceptions\AuthException;
use Application\Models\Usuario;
use Application\Services\User\PerfilApiWorkflowService;
use Application\Services\User\PerfilAvatarService;
use Application\Services\User\PerfilService;
use Application\Validators\PerfilValidator;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tests\Support\SessionIsolation;

class PerfilControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use SessionIsolation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetSessionState();
        $_POST = [];
        $_FILES = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    protected function tearDown(): void
    {
        $_POST = [];
        $_FILES = [];
        unset($_SERVER['REQUEST_METHOD']);
        $this->resetSessionState();
        parent::tearDown();
    }

    public function testShowReturnsSuccessResponse(): void
    {
        $this->seedAuthenticatedUserSession(101, 'Perfil User');

        $perfilService = Mockery::mock(PerfilService::class);
        $validator = Mockery::mock(PerfilValidator::class);

        $perfilService
            ->shouldReceive('obterPerfil')
            ->once()
            ->with(101)
            ->andReturn(['nome' => 'Perfil User']);

        $controller = new PerfilController($perfilService, $validator);

        $response = $controller->show();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'success' => true,
            'message' => 'Perfil carregado',
            'data' => [
                'user' => ['nome' => 'Perfil User'],
            ],
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testUpdateReturnsValidationErrorWhenValidatorFails(): void
    {
        $this->seedAuthenticatedUserSession(102, 'Perfil Invalid');
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'nome' => '',
            'email' => 'invalido@example.com',
        ];

        $perfilService = Mockery::mock(PerfilService::class);
        $validator = Mockery::mock(PerfilValidator::class);
        $validator
            ->shouldReceive('validate')
            ->once()
            ->with(Mockery::type(\Application\DTO\PerfilUpdateDTO::class), 102)
            ->andReturn([
                'nome' => 'Nome é obrigatório.',
            ]);

        $controller = new PerfilController($perfilService, $validator);

        $response = $controller->update();

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => [
                'nome' => 'Nome é obrigatório.',
            ],
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testUpdatePasswordReturnsValidationErrorFromWorkflowService(): void
    {
        $this->seedAuthenticatedUserSession(103, 'Perfil Password');
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'senha_atual' => 'Senha@123',
            'nova_senha' => 'NovaSenha@123',
            'conf_senha' => 'OutraSenha@123',
        ];

        $workflowService = Mockery::mock(PerfilApiWorkflowService::class);
        $workflowService
            ->shouldReceive('updatePassword')
            ->once()
            ->with(
                Mockery::type(Usuario::class),
                Mockery::on(static function (array $payload): bool {
                    return ($payload['senha_atual'] ?? null) === 'Senha@123'
                        && ($payload['nova_senha'] ?? null) === 'NovaSenha@123'
                        && ($payload['conf_senha'] ?? null) === 'OutraSenha@123';
                })
            )
            ->andReturn([
                'success' => false,
                'errors' => [
                    'conf_senha' => 'As senhas não coincidem.',
                ],
            ]);

        $controller = new PerfilController(
            null,
            null,
            $workflowService,
            Mockery::mock(PerfilAvatarService::class)
        );

        $response = $controller->updatePassword();

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => [
                'conf_senha' => 'As senhas não coincidem.',
            ],
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testShowThrowsAuthExceptionWhenSessionIsMissing(): void
    {
        $controller = new PerfilController(
            Mockery::mock(PerfilService::class),
            Mockery::mock(PerfilValidator::class),
        );

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Nao autenticado');

        $controller->show();
    }

    private function seedAuthenticatedUserSession(int $userId, string $name): void
    {
        $this->startIsolatedSession('perfil-controller-test');

        $user = new TestPerfilUser();
        $user->id = $userId;
        $user->nome = $name;
        $user->is_admin = 0;
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

final class TestPerfilUser extends Usuario
{
    public bool $saved = false;

    public function save(array $options = []): bool
    {
        $this->saved = true;

        return true;
    }
}
