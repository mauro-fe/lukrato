<?php

declare(strict_types=1);

namespace Application\UseCases\Metas;

use Application\Container\ApplicationContainer;
use Application\DTO\ServiceResultDTO;
use Application\Services\Demo\DemoPreviewService;
use Application\Services\Metas\MetaService;

class GetMetasListUseCase
{
    private readonly MetaService $metaService;
    private readonly DemoPreviewService $demoPreviewService;

    public function __construct(
        ?MetaService $metaService = null,
        ?DemoPreviewService $demoPreviewService = null
    ) {
        $this->metaService = ApplicationContainer::resolveOrNew($metaService, MetaService::class);
        $this->demoPreviewService = ApplicationContainer::resolveOrNew($demoPreviewService, DemoPreviewService::class);
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
