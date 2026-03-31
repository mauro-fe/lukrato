<?php

declare(strict_types=1);

namespace Application\UseCases\Metas;

use Application\DTO\ServiceResultDTO;
use Application\Services\Financeiro\MetaService;

class DeleteMetaUseCase
{
    public function __construct(
        private readonly MetaService $metaService = new MetaService()
    ) {
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
