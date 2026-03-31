<?php

declare(strict_types=1);

namespace Application\UseCases\Orcamentos;

use Application\DTO\ServiceResultDTO;
use Application\Services\Financeiro\OrcamentoService;
use Application\Validators\OrcamentoValidator;
use DomainException;

class BulkSaveOrcamentosUseCase
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
        $errors = OrcamentoValidator::validateBulk($payload);
        if ($errors !== []) {
            return ServiceResultDTO::validationFail($errors);
        }

        $mes = (int) ($payload['mes'] ?? date('m'));
        $ano = (int) ($payload['ano'] ?? date('Y'));

        try {
            $result = $this->orcamentoService->salvarMultiplos(
                $userId,
                $mes,
                $ano,
                is_array($payload['orcamentos'] ?? null) ? $payload['orcamentos'] : []
            );
        } catch (DomainException $e) {
            $message = trim($e->getMessage());

            return ServiceResultDTO::fail(
                $message !== '' ? $message : 'Não foi possivel salvar os orçamentos.',
                403
            );
        }

        return new ServiceResultDTO(
            success: true,
            message: 'Orçamentos salvos com sucesso!',
            data: $result,
            httpCode: 200
        );
    }
}
