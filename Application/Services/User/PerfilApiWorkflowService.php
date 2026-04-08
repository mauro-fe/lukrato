<?php

declare(strict_types=1);

namespace Application\Services\User;

use Application\Container\ApplicationContainer;
use Application\DTO\PerfilUpdateDTO;
use Application\Models\Usuario;
use Application\Services\Gamification\AchievementService;
use Application\Validators\PasswordStrengthValidator;
use Application\Validators\PerfilValidator;

class PerfilApiWorkflowService
{
    private PerfilService $perfilService;
    private PerfilValidator $validator;
    private AchievementService $achievementService;

    public function __construct(
        ?PerfilService $perfilService = null,
        ?PerfilValidator $validator = null,
        ?AchievementService $achievementService = null
    ) {
        $container = ApplicationContainer::getInstance() ?? ApplicationContainer::bootstrap();

        $this->perfilService = $perfilService ?? $container->make(PerfilService::class);
        $this->validator = $validator ?? $container->make(PerfilValidator::class);
        $this->achievementService = ApplicationContainer::resolveOrNew($achievementService, AchievementService::class);
    }

    public function getProfile(int $userId): ?array
    {
        return $this->perfilService->obterPerfil($userId);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function updateProfile(int $userId, array $payload): array
    {
        $dto = PerfilUpdateDTO::fromRequest($payload);
        $errors = $this->validator->validate($dto, $userId);

        if (!empty($errors)) {
            return [
                'success' => false,
                'errors' => $errors,
            ];
        }

        $profileUpdate = $this->perfilService->atualizarPerfil($userId, $dto);
        $updatedUser = (is_array($profileUpdate) && array_key_exists('user', $profileUpdate))
            ? $profileUpdate['user']
            : $profileUpdate;

        return [
            'success' => true,
            'user' => $updatedUser,
            'new_achievements' => $this->achievementService()->checkAndUnlockAchievements($userId, 'profile_update'),
            'email_change_pending' => (bool) ($profileUpdate['email_change_pending'] ?? false),
            'email_verification_sent' => (bool) ($profileUpdate['email_verification_sent'] ?? false),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function updatePassword(Usuario $user, array $payload): array
    {
        $currentPassword = (string) ($payload['senha_atual'] ?? '');
        $newPassword = (string) ($payload['nova_senha'] ?? '');
        $passwordConfirmation = (string) ($payload['conf_senha'] ?? '');

        if ($currentPassword === '' || $newPassword === '' || $passwordConfirmation === '') {
            return [
                'success' => false,
                'errors' => ['senha' => 'Todos os campos de senha são obrigatórios.'],
            ];
        }

        if (!password_verify($currentPassword, (string) $user->senha)) {
            return [
                'success' => false,
                'errors' => ['senha_atual' => 'Senha atual incorreta.'],
            ];
        }

        $passwordErrors = PasswordStrengthValidator::validate($newPassword);
        if (!empty($passwordErrors)) {
            return [
                'success' => false,
                'errors' => ['nova_senha' => implode(' ', $passwordErrors)],
            ];
        }

        if ($newPassword !== $passwordConfirmation) {
            return [
                'success' => false,
                'errors' => ['conf_senha' => 'As senhas não coincidem.'],
            ];
        }

        $user->senha = $newPassword;
        $user->save();

        return [
            'success' => true,
            'message' => 'Senha alterada com sucesso',
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function updateTheme(Usuario $user, array $payload): array
    {
        $theme = $payload['theme'] ?? null;

        if (!in_array($theme, ['light', 'dark'], true)) {
            return [
                'success' => false,
                'status' => 400,
                'message' => 'Tema inválido. Use "light" ou "dark"',
            ];
        }

        $user->theme_preference = $theme;
        $user->save();

        return [
            'success' => true,
            'data' => [
                'message' => 'Tema atualizado com sucesso',
                'theme' => $theme,
            ],
        ];
    }

    public function deleteAccount(int $userId): void
    {
        $this->perfilService->deletarConta($userId);
    }

    private function achievementService(): AchievementService
    {
        return $this->achievementService;
    }
}
