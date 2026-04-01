<?php

declare(strict_types=1);

namespace Application\UseCases\Financas;

use Application\DTO\ServiceResultDTO;
use Application\Services\Demo\DemoPreviewService;
use Application\Services\Metas\MetaService;
use Application\Services\Orcamentos\OrcamentoService;

class GetFinancasResumoUseCase
{
    public function __construct(
        private readonly MetaService $metaService = new MetaService(),
        private readonly OrcamentoService $orcamentoService = new OrcamentoService(),
        private readonly DemoPreviewService $demoPreviewService = new DemoPreviewService()
    ) {
    }

    public function execute(int $userId, int $mes, int $ano): ServiceResultDTO
    {
        if ($this->demoPreviewService->shouldUsePreview($userId)) {
            return new ServiceResultDTO(
                success: true,
                message: 'Resumo financeiro carregado',
                data: $this->demoPreviewService->financeSummary($mes, $ano),
                httpCode: 200
            );
        }

        return new ServiceResultDTO(
            success: true,
            message: 'Resumo financeiro carregado',
            data: [
                'orcamento' => $this->orcamentoService->resumo($userId, $mes, $ano),
                'metas' => $this->metaService->resumo($userId),
                'insights' => $this->orcamentoService->getInsights($userId, $mes, $ano),
                'mes' => $mes,
                'ano' => $ano,
            ],
            httpCode: 200
        );
    }
}
