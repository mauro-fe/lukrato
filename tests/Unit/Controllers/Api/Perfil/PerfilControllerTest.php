<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api\Perfil;

use Application\Controllers\Api\Perfil\PerfilController;
use Application\Core\Exceptions\AuthException;
use Application\Models\Usuario;
use Application\Services\User\PerfilApiWorkflowService;
use Application\UseCases\Perfil\AvatarUseCase;
use Application\UseCases\Perfil\DashboardPreferencesUseCase;
use Application\UseCases\Perfil\DeleteAccountUseCase;
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

        $workflowService = Mockery::mock(PerfilApiWorkflowService::class);
        $workflowService
            ->shouldReceive('getProfile')
            ->once()
            ->with(101)
            ->andReturn(['nome' => 'Perfil User']);

        $controller = new PerfilController($workflowService);

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

        $workflowService = Mockery::mock(PerfilApiWorkflowService::class);
        $workflowService
            ->shouldReceive('updateProfile')
            ->once()
            ->with(
                102,
                Mockery::on(static function (array $payload): bool {
                    return ($payload['nome'] ?? null) === ''
                        && ($payload['email'] ?? null) === 'invalido@example.com';
                })
            )
            ->andReturn([
                'success' => false,
                'errors' => [
                    'nome' => 'Nome é obrigatório.',
                ],
            ]);

        $controller = new PerfilController($workflowService);

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

        $controller = new PerfilController($workflowService);

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

        $controller = new PerfilController($workflowService);

        $response = $controller->update();
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'success' => true,
            'message' => 'Success',
            'data' => [
                'message' => 'Perfil atualizado com sucesso',
                'user' => ['nome' => 'Perfil Flags'],
                'new_achievements' => [],
                'email_change_pending' => true,
                'email_verification_sent' => true,
            ],
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testUpdatePasswordReturnsSuccessContractPayload(): void
    {
        $this->seedAuthenticatedUserSession(108, 'Perfil Password Success');
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'senha_atual' => 'Senha@123',
            'nova_senha' => 'NovaSenha@123',
            'conf_senha' => 'NovaSenha@123',
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
                        && ($payload['conf_senha'] ?? null) === 'NovaSenha@123';
                })
            )
            ->andReturn([
                'success' => true,
                'message' => 'Senha alterada com sucesso',
            ]);

        $controller = new PerfilController($workflowService);

        $response = $controller->updatePassword();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'success' => true,
            'message' => 'Success',
            'data' => [
                'message' => 'Senha alterada com sucesso',
            ],
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testUpdateThemeReturnsDomainErrorForInvalidTheme(): void
    {
        $this->seedAuthenticatedUserSession(109, 'Perfil Theme Invalid');
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['theme' => 'system'];

        $workflowService = Mockery::mock(PerfilApiWorkflowService::class);
        $workflowService
            ->shouldReceive('updateTheme')
            ->once()
            ->with(
                Mockery::type(Usuario::class),
                Mockery::on(static fn(array $payload): bool => ($payload['theme'] ?? null) === 'system')
            )
            ->andReturn([
                'success' => false,
                'status' => 400,
                'message' => 'Tema inválido. Use "light" ou "dark"',
            ]);

        $controller = new PerfilController($workflowService);

        $response = $controller->updateTheme();

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'Tema inválido. Use "light" ou "dark"',
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testUpdateThemeReturnsSuccessContractPayload(): void
    {
        $this->seedAuthenticatedUserSession(110, 'Perfil Theme Success');
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['theme' => 'dark'];

        $workflowService = Mockery::mock(PerfilApiWorkflowService::class);
        $workflowService
            ->shouldReceive('updateTheme')
            ->once()
            ->with(
                Mockery::type(Usuario::class),
                Mockery::on(static fn(array $payload): bool => ($payload['theme'] ?? null) === 'dark')
            )
            ->andReturn([
                'success' => true,
                'data' => [
                    'message' => 'Tema atualizado com sucesso',
                    'theme' => 'dark',
                ],
            ]);

        $controller = new PerfilController($workflowService);

        $response = $controller->updateTheme();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'success' => true,
            'message' => 'Success',
            'data' => [
                'message' => 'Tema atualizado com sucesso',
                'theme' => 'dark',
            ],
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testUploadAvatarReturnsDomainErrorWhenFileIsMissing(): void
    {
        $this->seedAuthenticatedUserSession(111, 'Perfil Avatar Error');
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $avatarUseCase = Mockery::mock(AvatarUseCase::class);
        $avatarUseCase
            ->shouldReceive('upload')
            ->once()
            ->with(Mockery::type(Usuario::class), null)
            ->andReturn([
                'success' => false,
                'status' => 400,
                'message' => 'Nenhuma imagem enviada ou erro no upload',
            ]);

        $controller = new PerfilController(
            Mockery::mock(PerfilApiWorkflowService::class),
            Mockery::mock(DashboardPreferencesUseCase::class),
            $avatarUseCase
        );

        $response = $controller->uploadAvatar();

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'Nenhuma imagem enviada ou erro no upload',
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testUploadAvatarReturnsSuccessContractPayload(): void
    {
        $this->seedAuthenticatedUserSession(112, 'Perfil Avatar Success');
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_FILES['avatar'] = [
            'name' => 'avatar.webp',
            'type' => 'image/webp',
            'tmp_name' => 'C:/tmp/avatar.webp',
            'error' => UPLOAD_ERR_OK,
            'size' => 12345,
        ];

        $avatarUseCase = Mockery::mock(AvatarUseCase::class);
        $avatarUseCase
            ->shouldReceive('upload')
            ->once()
            ->with(
                Mockery::type(Usuario::class),
                [
                    'name' => 'avatar.webp',
                    'type' => 'image/webp',
                    'tmp_name' => 'C:/tmp/avatar.webp',
                    'error' => UPLOAD_ERR_OK,
                    'size' => 12345,
                ]
            )
            ->andReturn([
                'success' => true,
                'data' => [
                    'message' => 'Foto de perfil atualizada!',
                    'avatar' => 'https://example.test/assets/uploads/avatars/avatar_112.webp',
                    'avatar_settings' => [
                        'position_x' => 50,
                        'position_y' => 50,
                        'zoom' => 1,
                    ],
                ],
            ]);

        $controller = new PerfilController(
            Mockery::mock(PerfilApiWorkflowService::class),
            Mockery::mock(DashboardPreferencesUseCase::class),
            $avatarUseCase
        );

        $response = $controller->uploadAvatar();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'success' => true,
            'message' => 'Success',
            'data' => [
                'message' => 'Foto de perfil atualizada!',
                'avatar' => 'https://example.test/assets/uploads/avatars/avatar_112.webp',
                'avatar_settings' => [
                    'position_x' => 50,
                    'position_y' => 50,
                    'zoom' => 1,
                ],
            ],
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testUpdateAvatarPreferencesReturnsSuccessContractPayload(): void
    {
        $this->seedAuthenticatedUserSession(113, 'Perfil Avatar Preferences');
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'position_x' => '42',
            'position_y' => '58',
            'zoom' => '1.2',
        ];

        $avatarUseCase = Mockery::mock(AvatarUseCase::class);
        $avatarUseCase
            ->shouldReceive('updatePreferences')
            ->once()
            ->with(
                Mockery::type(Usuario::class),
                [
                    'position_x' => '42',
                    'position_y' => '58',
                    'zoom' => '1.2',
                ]
            )
            ->andReturn([
                'data' => [
                    'message' => 'Enquadramento da foto atualizado.',
                    'avatar_settings' => [
                        'position_x' => 42,
                        'position_y' => 58,
                        'zoom' => 1.2,
                    ],
                ],
            ]);

        $controller = new PerfilController(
            Mockery::mock(PerfilApiWorkflowService::class),
            Mockery::mock(DashboardPreferencesUseCase::class),
            $avatarUseCase
        );

        $response = $controller->updateAvatarPreferences();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'success' => true,
            'message' => 'Success',
            'data' => [
                'message' => 'Enquadramento da foto atualizado.',
                'avatar_settings' => [
                    'position_x' => 42,
                    'position_y' => 58,
                    'zoom' => 1.2,
                ],
            ],
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testShowThrowsAuthExceptionWhenSessionIsMissing(): void
    {
        $controller = new PerfilController(Mockery::mock(PerfilApiWorkflowService::class));

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
            Mockery::mock(PerfilApiWorkflowService::class),
            $dashboardUseCase
        );

        $response = $controller->getDashboardPreferences();
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'success' => true,
            'message' => 'Success',
            'data' => [
                'preferences' => ['toggleGrafico' => true],
            ],
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
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
            Mockery::mock(PerfilApiWorkflowService::class),
            $dashboardUseCase
        );

        $response = $controller->updateDashboardPreferences();
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'success' => true,
            'message' => 'Preferências do dashboard atualizadas',
            'data' => [
                'preferences' => ['toggleMetas' => true],
            ],
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
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
            Mockery::mock(PerfilApiWorkflowService::class),
            Mockery::mock(DashboardPreferencesUseCase::class),
            Mockery::mock(AvatarUseCase::class),
            $deleteUseCase
        );

        $response = $controller->delete();
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'success' => true,
            'message' => 'Success',
            'data' => [
                'message' => 'Conta excluída com sucesso',
            ],
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testRemoveAvatarReturnsSuccessContractPayload(): void
    {
        $this->seedAuthenticatedUserSession(114, 'Perfil Avatar Remove');
        $_SERVER['REQUEST_METHOD'] = 'DELETE';

        $avatarUseCase = Mockery::mock(AvatarUseCase::class);
        $avatarUseCase
            ->shouldReceive('remove')
            ->once()
            ->with(Mockery::type(Usuario::class))
            ->andReturn([
                'data' => [
                    'message' => 'Foto de perfil removida',
                    'avatar' => '',
                    'avatar_settings' => [
                        'position_x' => 50,
                        'position_y' => 50,
                        'zoom' => 1,
                    ],
                ],
            ]);

        $controller = new PerfilController(
            Mockery::mock(PerfilApiWorkflowService::class),
            Mockery::mock(DashboardPreferencesUseCase::class),
            $avatarUseCase
        );

        $response = $controller->removeAvatar();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'success' => true,
            'message' => 'Success',
            'data' => [
                'message' => 'Foto de perfil removida',
                'avatar' => '',
                'avatar_settings' => [
                    'position_x' => 50,
                    'position_y' => 50,
                    'zoom' => 1,
                ],
            ],
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
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
