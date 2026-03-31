<?php

declare(strict_types=1);

namespace Application\UseCases\Metas;

use Application\DTO\ServiceResultDTO;
use Application\Services\Financeiro\MetaService;
use Application\Services\Gamification\AchievementService;
use Application\Validators\MetaValidator;
use DomainException;

class CreateMetaUseCase
{
    public function __construct(
        private readonly MetaService $metaService = new MetaService(),
        private readonly AchievementService $achievementService = new AchievementService()
    ) {
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
