<?php

declare(strict_types=1);

namespace Application\UseCases\Orcamentos;

use Application\DTO\ServiceResultDTO;
use Application\Services\Orcamentos\OrcamentoService;

class CopyOrcamentosMesUseCase
{
    private readonly OrcamentoService $orcamentoService;

    public function __construct(
        ?OrcamentoService $orcamentoService = null
    ) {
        $this->orcamentoService = $orcamentoService ?? new OrcamentoService();
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function execute(int $userId, array $payload): ServiceResultDTO
    {
        $mes = (int) ($payload['mes'] ?? date('m'));
        $ano = (int) ($payload['ano'] ?? date('Y'));

        $result = $this->orcamentoService->copiarMesAnterior($userId, $mes, $ano);
        $copiados = (int) ($result['copiados'] ?? 0);

        return new ServiceResultDTO(
            success: true,
            message: "{$copiados} orçamentos copiados!",
            data: $result,
            httpCode: 200
        );
    }
}
