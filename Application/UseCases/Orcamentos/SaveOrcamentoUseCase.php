<?php

declare(strict_types=1);

namespace Application\UseCases\Orcamentos;

use Application\Container\ApplicationContainer;
use Application\DTO\ServiceResultDTO;
use Application\Services\Orcamentos\OrcamentoService;
use Application\Validators\OrcamentoValidator;
use DomainException;

class SaveOrcamentoUseCase
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
