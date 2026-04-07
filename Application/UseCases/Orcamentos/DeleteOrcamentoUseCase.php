<?php

declare(strict_types=1);

namespace Application\UseCases\Orcamentos;

use Application\DTO\ServiceResultDTO;
use Application\Services\Orcamentos\OrcamentoService;

class DeleteOrcamentoUseCase
{
    private readonly OrcamentoService $orcamentoService;

    public function __construct(
        ?OrcamentoService $orcamentoService = null
    ) {
        $this->orcamentoService = $orcamentoService ?? new OrcamentoService();
    }

    public function execute(int $userId, int $orcamentoId): ServiceResultDTO
    {
        $deleted = $this->orcamentoService->remover($userId, $orcamentoId);
        if (!$deleted) {
            return ServiceResultDTO::fail('Orçamento não encontrado.', 404);
        }

        return new ServiceResultDTO(
            success: true,
            message: 'Orçamento removido com sucesso!',
            data: [],
            httpCode: 200
        );
    }
}
