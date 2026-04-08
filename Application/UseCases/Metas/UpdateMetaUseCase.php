<?php

declare(strict_types=1);

namespace Application\UseCases\Metas;

use Application\Container\ApplicationContainer;
use Application\DTO\ServiceResultDTO;
use Application\Services\Metas\MetaService;
use Application\Validators\MetaValidator;

class UpdateMetaUseCase
{
    private readonly MetaService $metaService;

    public function __construct(
        ?MetaService $metaService = null
    ) {
        $this->metaService = ApplicationContainer::resolveOrNew($metaService, MetaService::class);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function execute(int $userId, int $metaId, array $payload): ServiceResultDTO
    {
        $errors = MetaValidator::validateUpdate($payload);
        if ($errors !== []) {
            return ServiceResultDTO::validationFail($errors);
        }

        $meta = $this->metaService->atualizar($userId, $metaId, $payload);
        if (!$meta) {
            return ServiceResultDTO::fail('Meta não encontrada.', 404);
        }

        return new ServiceResultDTO(
            success: true,
            message: 'Meta atualizada com sucesso!',
            data: $meta,
            httpCode: 200
        );
    }
}
