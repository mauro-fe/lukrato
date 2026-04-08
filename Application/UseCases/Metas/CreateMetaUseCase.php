<?php

declare(strict_types=1);

namespace Application\UseCases\Metas;

use Application\Container\ApplicationContainer;
use Application\DTO\ServiceResultDTO;
use Application\Services\Metas\MetaService;
use Application\Services\Gamification\AchievementService;
use Application\Validators\MetaValidator;
use DomainException;

class CreateMetaUseCase
{
    private readonly MetaService $metaService;
    private readonly AchievementService $achievementService;

    public function __construct(
        ?MetaService $metaService = null,
        ?AchievementService $achievementService = null
    ) {
        $this->metaService = ApplicationContainer::resolveOrNew($metaService, MetaService::class);
        $this->achievementService = ApplicationContainer::resolveOrNew($achievementService, AchievementService::class);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function execute(int $userId, array $payload): ServiceResultDTO
    {
        $errors = MetaValidator::validateCreate($payload);
        if ($errors !== []) {
            return ServiceResultDTO::validationFail($errors);
        }

        try {
            $meta = $this->metaService->criar($userId, $payload);
        } catch (DomainException $e) {
            $message = trim($e->getMessage());

            return ServiceResultDTO::fail(
                $message !== '' ? $message : 'Nao foi possivel criar a meta.',
                403
            );
        }

        $newAchievements = $this->achievementService->checkAndUnlockAchievements($userId, 'meta_criada');
        $gamification = [];
        if (!empty($newAchievements)) {
            $gamification['achievements'] = $newAchievements;
        }

        $data = array_merge(
            ['meta' => $meta],
            $gamification !== [] ? ['gamification' => $gamification] : []
        );

        return new ServiceResultDTO(
            success: true,
            message: 'Meta criada com sucesso!',
            data: $data,
            httpCode: 201
        );
    }
}
