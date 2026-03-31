<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api\Perfil;

use Application\Controllers\Api\Perfil\PerfilController;
use Application\Core\Exceptions\AuthException;
use Application\Models\Usuario;
use Application\Services\User\PerfilApiWorkflowService;
use Application\Services\User\PerfilAvatarService;
use Application\Services\User\PerfilService;
use Application\UseCases\Perfil\AvatarUseCase;
use Application\UseCases\Perfil\DashboardPreferencesUseCase;
use Application\UseCases\Perfil\DeleteAccountUseCase;
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

    public function testUpdateReturnsSuccessIncludingEmailChangeFlags(): void
    {
        $this->seedAuthenticatedUserSession(104, 'Perfil Flags');
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'nome' => 'Perfil Flags',
            'email' => 'novo@email.com',
        ];

        $workflowService = Mockery::mock(PerfilApiWorkflowService::class);
        $workflowService
            ->shouldReceive('updateProfile')
            ->once()
            ->with(
                104,
                Mockery::on(static function (array $payload): bool {
                    return ($payload['nome'] ?? null) === 'Perfil Flags'
                        && ($payload['email'] ?? null) === 'novo@email.com';
                })
            )
            ->andReturn([
                'success' => true,
                'user' => ['nome' => 'Perfil Flags'],
                'new_achievements' => [],
                'email_change_pending' => true,
                'email_verification_sent' => true,
            ]);

        $controller = new PerfilController(
            null,
            null,
            $workflowService,
            Mockery::mock(PerfilAvatarService::class)
        );

        $response = $controller->update();
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($payload['success']);
        $this->assertTrue($payload['data']['email_change_pending']);
        $this->assertTrue($payload['data']['email_verification_sent']);
    }

    public function testShowThrowsAuthExceptionWhenSessionIsMissing(): void
    {
        $controller = new PerfilController(
            Mockery::mock(PerfilService::class),
            Mockery::mock(PerfilValidator::class),
        );

        $this->expectException(AuthException::class);
        $this->expectExceptionMessageMatches('/N[ãa]o autenticado/u');

        $controller->show();
    }

    public function testGetDashboardPreferencesReturnsUseCasePayload(): void
    {
        $this->seedAuthenticatedUserSession(105, 'Perfil Dashboard');

        $dashboardUseCase = Mockery::mock(DashboardPreferencesUseCase::class);
        $dashboardUseCase
            ->shouldReceive('get')
            ->once()
            ->with(Mockery::type(Usuario::class))
            ->andReturn([
                'preferences' => ['toggleGrafico' => true],
            ]);

        $controller = new PerfilController(
            null,
            null,
            Mockery::mock(PerfilApiWorkflowService::class),
            Mockery::mock(PerfilAvatarService::class),
            $dashboardUseCase
        );

        $response = $controller->getDashboardPreferences();
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($payload['success']);
        $this->assertSame(['toggleGrafico' => true], $payload['data']['preferences'] ?? null);
    }

    public function testUpdateDashboardPreferencesReturnsSuccessFromUseCase(): void
    {
        $this->seedAuthenticatedUserSession(106, 'Perfil Dashboard Update');
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['toggleMetas' => '1'];

        $dashboardUseCase = Mockery::mock(DashboardPreferencesUseCase::class);
        $dashboardUseCase
            ->shouldReceive('update')
            ->once()
            ->with(
                Mockery::type(Usuario::class),
                Mockery::on(static fn(array $payload): bool => ($payload['toggleMetas'] ?? null) === '1')
            )
            ->andReturn([
                'preferences' => ['toggleMetas' => true],
            ]);

        $controller = new PerfilController(
            null,
            null,
            Mockery::mock(PerfilApiWorkflowService::class),
            Mockery::mock(PerfilAvatarService::class),
            $dashboardUseCase
        );

        $response = $controller->updateDashboardPreferences();
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($payload['success']);
        $this->assertSame('Preferências do dashboard atualizadas', $payload['message'] ?? null);
        $this->assertSame(['toggleMetas' => true], $payload['data']['preferences'] ?? null);
    }

    public function testDeleteReturnsSuccessFromUseCase(): void
    {
        $this->seedAuthenticatedUserSession(107, 'Perfil Delete');

        $deleteUseCase = Mockery::mock(DeleteAccountUseCase::class);
        $deleteUseCase
            ->shouldReceive('execute')
            ->once()
            ->with(107)
            ->andReturn([
                'message' => 'Conta excluída com sucesso',
            ]);

        $controller = new PerfilController(
            null,
            null,
            Mockery::mock(PerfilApiWorkflowService::class),
            Mockery::mock(PerfilAvatarService::class),
            Mockery::mock(DashboardPreferencesUseCase::class),
            Mockery::mock(AvatarUseCase::class),
            $deleteUseCase
        );

        $response = $controller->delete();
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($payload['success']);
        $this->assertSame('Conta excluída com sucesso', $payload['data']['message'] ?? null);
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
