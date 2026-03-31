<?php

declare(strict_types=1);

namespace Application\UseCases\Orcamentos;

use Application\DTO\ServiceResultDTO;
use Application\Services\Financeiro\OrcamentoService;

class ApplyOrcamentoSugestoesUseCase
{
    public function __construct(
        private readonly OrcamentoService $orcamentoService = new OrcamentoService()
    ) {
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
