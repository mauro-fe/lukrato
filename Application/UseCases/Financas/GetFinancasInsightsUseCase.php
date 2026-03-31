<?php

declare(strict_types=1);

namespace Application\UseCases\Financas;

use Application\DTO\ServiceResultDTO;
use Application\Services\Demo\DemoPreviewService;
use Application\Services\Financeiro\OrcamentoService;

class GetFinancasInsightsUseCase
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
                message: 'Insights carregados',
                data: $this->demoPreviewService->financeInsights($mes, $ano),
                httpCode: 200
            );
        }

        return new ServiceResultDTO(
            success: true,
            message: 'Insights carregados',
            data: $this->orcamentoService->getInsights($userId, $mes, $ano),
            httpCode: 200
        );
    }
}
