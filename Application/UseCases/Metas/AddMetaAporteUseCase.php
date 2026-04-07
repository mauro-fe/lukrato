<?php

declare(strict_types=1);

namespace Application\UseCases\Metas;

use Application\DTO\ServiceResultDTO;
use Application\Services\Metas\MetaService;
use Application\Services\Gamification\AchievementService;
use Application\Validators\MetaValidator;
use DomainException;

class AddMetaAporteUseCase
{
    private readonly MetaService $metaService;
    private readonly AchievementService $achievementService;

    public function __construct(
        ?MetaService $metaService = null,
        ?AchievementService $achievementService = null
    ) {
        $this->metaService = $metaService ?? new MetaService();
        $this->achievementService = $achievementService ?? new AchievementService();
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function execute(int $userId, int $metaId, array $payload): ServiceResultDTO
    {
        $errors = MetaValidator::validateAporte($payload);
        if ($errors !== []) {
            return ServiceResultDTO::validationFail($errors);
        }

        try {
            $meta = $this->metaService->adicionarAporte($userId, $metaId, (float) $payload['valor']);
        } catch (DomainException $e) {
            $message = trim($e->getMessage());

            return ServiceResultDTO::fail(
                $message !== '' ? $message : 'Não foi possivel registrar o aporte.',
                400
            );
        }

        if (!$meta) {
            return ServiceResultDTO::fail('Meta não encontrada.', 404);
        }

        $newAchievements = $this->achievementService->checkAndUnlockAchievements($userId, 'meta_aporte');
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
            message: 'Aporte registrado com sucesso!',
            data: $data,
            httpCode: 200
        );
    }
}
