<?php

declare(strict_types=1);

namespace Application\UseCases\Orcamentos;

use Application\DTO\ServiceResultDTO;
use Application\Services\Demo\DemoPreviewService;
use Application\Services\Orcamentos\OrcamentoService;

class GetOrcamentosListUseCase
{
    public function __construct(
        private readonly OrcamentoService $orcamentoService = new OrcamentoService(),
        private readonly DemoPreviewService $demoPreviewService = new DemoPreviewService()
    ) {
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
