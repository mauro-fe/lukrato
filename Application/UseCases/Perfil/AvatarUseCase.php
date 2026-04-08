<?php

declare(strict_types=1);

namespace Application\UseCases\Perfil;

use Application\Container\ApplicationContainer;
use Application\Models\Usuario;
use Application\Services\User\PerfilAvatarService;

class AvatarUseCase
{
    private PerfilAvatarService $avatarService;

    public function __construct(
        ?PerfilAvatarService $avatarService = null
    ) {
        $this->avatarService = ApplicationContainer::resolveOrNew($avatarService, PerfilAvatarService::class);
    }

    /**
     * @param array<string, mixed>|null $file
     * @return array<string, mixed>
     */
    public function upload(Usuario $user, ?array $file): array
    {
        return $this->avatarService->uploadAvatar($user, $file);
    }

    /**
     * @return array<string, mixed>
     */
    public function remove(Usuario $user): array
    {
        return $this->avatarService->removeAvatar($user);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function updatePreferences(Usuario $user, array $payload): array
    {
        return $this->avatarService->updateAvatarPreferences($user, $payload);
    }
}
