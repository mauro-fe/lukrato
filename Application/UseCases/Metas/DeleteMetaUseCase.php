<?php

declare(strict_types=1);

namespace Application\UseCases\Metas;

use Application\Container\ApplicationContainer;
use Application\DTO\ServiceResultDTO;
use Application\Services\Metas\MetaService;

class DeleteMetaUseCase
{
    private readonly MetaService $metaService;

    public function __construct(
        ?MetaService $metaService = null
    ) {
        $this->metaService = ApplicationContainer::resolveOrNew($metaService, MetaService::class);
    }

    public function execute(int $userId, int $metaId): ServiceResultDTO
    {
        $deleted = $this->metaService->remover($userId, $metaId);
        if (!$deleted) {
            return ServiceResultDTO::fail('Meta não encontrada.', 404);
        }

        return new ServiceResultDTO(
            success: true,
            message: 'Meta removida com sucesso!',
            data: [],
            httpCode: 200
        );
    }
}
