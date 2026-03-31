<?php

declare(strict_types=1);

namespace Application\UseCases\Metas;

use Application\DTO\ServiceResultDTO;
use Application\Services\Financeiro\MetaService;

class GetMetaTemplatesUseCase
{
    public function __construct(
        private readonly MetaService $metaService = new MetaService()
    ) {
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
