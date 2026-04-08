<?php

declare(strict_types=1);

namespace Application\UseCases\Orcamentos;

use Application\Container\ApplicationContainer;
use Application\DTO\ServiceResultDTO;
use Application\Services\Orcamentos\OrcamentoService;

class ApplyOrcamentoSugestoesUseCase
{
    private readonly OrcamentoService $orcamentoService;

    public function __construct(
        ?OrcamentoService $orcamentoService = null
    ) {
        $this->orcamentoService = ApplicationContainer::resolveOrNew($orcamentoService, OrcamentoService::class);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function execute(int $userId, array $payload): ServiceResultDTO
    {
        $mes = (int) ($payload['mes'] ?? date('m'));
        $ano = (int) ($payload['ano'] ?? date('Y'));
        $sugestoes = is_array($payload['sugestoes'] ?? null) ? $payload['sugestoes'] : [];

        $result = $this->orcamentoService->aplicarSugestoes($userId, $mes, $ano, $sugestoes);

        return new ServiceResultDTO(
            success: true,
            message: 'Sugestões aplicadas com sucesso!',
            data: $result,
            httpCode: 200
        );
    }
}
