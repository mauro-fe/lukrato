<?php

declare(strict_types=1);

namespace Application\UseCases\Orcamentos;

use Application\Container\ApplicationContainer;
use Application\DTO\ServiceResultDTO;
use Application\Services\Demo\DemoPreviewService;
use Application\Services\Orcamentos\OrcamentoService;

class GetOrcamentosListUseCase
{
    private readonly OrcamentoService $orcamentoService;
    private readonly DemoPreviewService $demoPreviewService;

    public function __construct(
        ?OrcamentoService $orcamentoService = null,
        ?DemoPreviewService $demoPreviewService = null
    ) {
        $this->orcamentoService = ApplicationContainer::resolveOrNew($orcamentoService, OrcamentoService::class);
        $this->demoPreviewService = ApplicationContainer::resolveOrNew($demoPreviewService, DemoPreviewService::class);
    }

    public function execute(int $userId, int $mes, int $ano): ServiceResultDTO
    {
        if ($this->demoPreviewService->shouldUsePreview($userId)) {
            return new ServiceResultDTO(
                success: true,
                message: 'Orçamentos carregados',
                data: $this->demoPreviewService->orcamentos($mes, $ano),
                httpCode: 200
            );
        }

        return new ServiceResultDTO(
            success: true,
            message: 'Orçamentos carregados',
            data: $this->orcamentoService->listarComProgresso($userId, $mes, $ano),
            httpCode: 200
        );
    }
}
