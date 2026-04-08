<?php

declare(strict_types=1);

namespace Application\UseCases\Financas;

use Application\Container\ApplicationContainer;
use Application\DTO\ServiceResultDTO;
use Application\Services\Demo\DemoPreviewService;
use Application\Services\Metas\MetaService;
use Application\Services\Orcamentos\OrcamentoService;

class GetFinancasResumoUseCase
{
    private readonly MetaService $metaService;
    private readonly OrcamentoService $orcamentoService;
    private readonly DemoPreviewService $demoPreviewService;

    public function __construct(
        ?MetaService $metaService = null,
        ?OrcamentoService $orcamentoService = null,
        ?DemoPreviewService $demoPreviewService = null
    ) {
        $this->metaService = ApplicationContainer::resolveOrNew($metaService, MetaService::class);
        $this->orcamentoService = ApplicationContainer::resolveOrNew($orcamentoService, OrcamentoService::class);
        $this->demoPreviewService = ApplicationContainer::resolveOrNew($demoPreviewService, DemoPreviewService::class);
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
