<?php

declare(strict_types=1);

namespace Application\UseCases\Metas;

use Application\DTO\ServiceResultDTO;
use Application\Services\Demo\DemoPreviewService;
use Application\Services\Metas\MetaService;

class GetMetasListUseCase
{
    public function __construct(
        private readonly MetaService $metaService = new MetaService(),
        private readonly DemoPreviewService $demoPreviewService = new DemoPreviewService()
    ) {
    }

    public function execute(int $userId, ?string $status): ServiceResultDTO
    {
        if ($this->demoPreviewService->shouldUsePreview($userId)) {
            return new ServiceResultDTO(
                success: true,
                message: 'Metas carregadas',
                data: $this->demoPreviewService->metas($status),
                httpCode: 200
            );
        }

        return new ServiceResultDTO(
            success: true,
            message: 'Metas carregadas',
            data: $this->metaService->listar($userId, $status),
            httpCode: 200
        );
    }
}
