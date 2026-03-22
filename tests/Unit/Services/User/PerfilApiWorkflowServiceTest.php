<?php

declare(strict_types=1);

namespace Tests\Unit\Services\User;

use Application\DTO\PerfilUpdateDTO;
use Application\Models\Usuario;
use Application\Services\Gamification\AchievementService;
use Application\Services\User\PerfilApiWorkflowService;
use Application\Services\User\PerfilService;
use Application\Validators\PerfilValidator;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class PerfilApiWorkflowServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testUpdateProfileReturnsValidationErrorsWithoutUpdatingProfile(): void
    {
        $perfilService = Mockery::mock(PerfilService::class);
        $validator = Mockery::mock(PerfilValidator::class);
        $validator
            ->shouldReceive('validate')
            ->once()
            ->with(Mockery::type(PerfilUpdateDTO::class), 21)
            ->andReturn([
                'nome' => 'Nome Ã© obrigatÃ³rio.',
            ]);

        $perfilService->shouldNotReceive('atualizarPerfil');

        $service = new PerfilApiWorkflowService($perfilService, $validator);

        $result = $service->updateProfile(21, [
            'nome' => '',
            'email' => 'teste@example.com',
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame([
            'nome' => 'Nome Ã© obrigatÃ³rio.',
        ], $result['errors']);
    }

    public function testUpdateProfileReturnsUpdatedUserAndAchievements(): void
    {
        $perfilService = Mockery::mock(PerfilService::class);
        $perfilService
            ->shouldReceive('atualizarPerfil')
            ->once()
            ->with(22, Mockery::type(PerfilUpdateDTO::class))
            ->andReturn([
                'nome' => 'Perfil Atualizado',
            ]);

        $validator = Mockery::mock(PerfilValidator::class);
        $validator
            ->shouldReceive('validate')
            ->once()
            ->with(Mockery::type(PerfilUpdateDTO::class), 22)
            ->andReturn([]);

        $achievementService = Mockery::mock(AchievementService::class);
        $achievementService
            ->shouldReceive('checkAndUnlockAchievements')
            ->once()
            ->with(22, 'profile_update')
            ->andReturn([
                ['slug' => 'profile_update'],
            ]);

        $service = new PerfilApiWorkflowService($perfilService, $validator, $achievementService);

        $result = $service->updateProfile(22, [
            'nome' => 'Perfil Atualizado',
            'email' => 'perfil@example.com',
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame(['nome' => 'Perfil Atualizado'], $result['user']);
        $this->assertSame([
            ['slug' => 'profile_update'],
        ], $result['new_achievements']);
    }

    public function testUpdatePasswordRejectsMismatchedConfirmation(): void
    {
        $service = new PerfilApiWorkflowService(
            Mockery::mock(PerfilService::class),
            Mockery::mock(PerfilValidator::class)
        );

        $user = new class extends Usuario {
            public bool $saved = false;

            public function save(array $options = []): bool
            {
                $this->saved = true;

                return true;
            }
        };
        $user->senha = password_hash('Senha@123', PASSWORD_DEFAULT);

        $result = $service->updatePassword($user, [
            'senha_atual' => 'Senha@123',
            'nova_senha' => 'NovaSenha@123',
            'conf_senha' => 'OutraSenha@123',
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame([
            'conf_senha' => 'As senhas nÃ£o coincidem.',
        ], $result['errors']);
        $this->assertFalse($user->saved);
    }

    public function testUpdateThemeRejectsInvalidTheme(): void
    {
        $service = new PerfilApiWorkflowService(
            Mockery::mock(PerfilService::class),
            Mockery::mock(PerfilValidator::class)
        );

        $user = new class extends Usuario {
            public function save(array $options = []): bool
            {
                return true;
            }
        };

        $result = $service->updateTheme($user, ['theme' => 'solarized']);

        $this->assertFalse($result['success']);
        $this->assertSame(400, $result['status']);
        $this->assertSame('Tema invÃ¡lido. Use "light" ou "dark"', $result['message']);
    }
}
