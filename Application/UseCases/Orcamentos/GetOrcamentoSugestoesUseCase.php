<?php

declare(strict_types=1);

namespace Application\UseCases\Orcamentos;

use Application\DTO\ServiceResultDTO;
use Application\Services\Orcamentos\OrcamentoService;

class GetOrcamentoSugestoesUseCase
{
    public function __construct(
        private readonly OrcamentoService $orcamentoService = new OrcamentoService()
    ) {
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
