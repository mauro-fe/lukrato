<?php

declare(strict_types=1);

namespace Application\UseCases\Orcamentos;

use Application\DTO\ServiceResultDTO;
use Application\Services\Financeiro\OrcamentoService;
use Application\Validators\OrcamentoValidator;
use DomainException;

class SaveOrcamentoUseCase
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
        $errors = OrcamentoValidator::validateSave($payload);
        if ($errors !== []) {
            return ServiceResultDTO::validationFail($errors);
        }

        $mes = (int) ($payload['mes'] ?? date('m'));
        $ano = (int) ($payload['ano'] ?? date('Y'));

        try {
            $orcamentos = $this->orcamentoService->salvar(
                $userId,
                (int) $payload['categoria_id'],
                $mes,
                $ano,
                $payload
            );
        } catch (DomainException $e) {
            $message = trim($e->getMessage());

            return ServiceResultDTO::fail(
                $message !== '' ? $message : 'Não foi possivel salvar o orcamento.',
                403
            );
        }

        return new ServiceResultDTO(
            success: true,
            message: 'Orçamento salvo com sucesso!',
            data: $orcamentos,
            httpCode: 200
        );
    }
}
