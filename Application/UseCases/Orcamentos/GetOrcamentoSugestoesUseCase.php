<?php

declare(strict_types=1);

namespace Application\UseCases\Orcamentos;

use Application\Container\ApplicationContainer;
use Application\DTO\ServiceResultDTO;
use Application\Services\Orcamentos\OrcamentoService;

class GetOrcamentoSugestoesUseCase
{
    private readonly OrcamentoService $orcamentoService;

    public function __construct(
        ?OrcamentoService $orcamentoService = null
    ) {
        $this->orcamentoService = ApplicationContainer::resolveOrNew($orcamentoService, OrcamentoService::class);
    }

    public function execute(int $userId): ServiceResultDTO
    {
        $sugestoes = $this->orcamentoService->autoSugerir($userId);

        return new ServiceResultDTO(
            success: true,
            message: 'Sugestões calculadas',
            data: $sugestoes,
            httpCode: 200
        );
    }
}
