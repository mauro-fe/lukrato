<?php

declare(strict_types=1);

namespace Application\UseCases\Metas;

use Application\DTO\ServiceResultDTO;
use Application\Services\Financeiro\MetaService;
use Application\Validators\MetaValidator;

class UpdateMetaUseCase
{
    public function __construct(
        private readonly MetaService $metaService = new MetaService()
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function execute(int $userId, int $metaId, array $payload): ServiceResultDTO
    {
        $errors = MetaValidator::validateUpdate($payload);
        if ($errors !== []) {
            return ServiceResultDTO::validationFail($errors);
        }

        $meta = $this->metaService->atualizar($userId, $metaId, $payload);
        if (!$meta) {
            return ServiceResultDTO::fail('Meta não encontrada.', 404);
        }

        return new ServiceResultDTO(
            success: true,
            message: 'Meta atualizada com sucesso!',
            data: $meta,
            httpCode: 200
        );
    }
}
