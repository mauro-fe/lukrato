<?php

declare(strict_types=1);

namespace Application\UseCases\Metas;

use Application\DTO\ServiceResultDTO;
use Application\Services\Metas\MetaService;

class GetMetaTemplatesUseCase
{
    private readonly MetaService $metaService;

    public function __construct(
        ?MetaService $metaService = null
    ) {
        $this->metaService = $metaService ?? new MetaService();
    }

    public function execute(int $userId): ServiceResultDTO
    {
        $templates = $this->metaService->getTemplates();
        $sugestaoEmergencia = $this->metaService->sugerirReservaEmergencia($userId);

        foreach ($templates as &$template) {
            if (($template['tipo'] ?? null) === 'emergencia' && $sugestaoEmergencia > 0) {
                $template['valor_sugerido'] = $sugestaoEmergencia;
            }
        }
        unset($template);

        return new ServiceResultDTO(
            success: true,
            message: 'Templates carregados',
            data: $templates,
            httpCode: 200
        );
    }
}
