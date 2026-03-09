<?php

declare(strict_types=1);

namespace Application\Services\AI\Handlers;

use Application\DTO\AI\AIRequestDTO;
use Application\DTO\AI\AIResponseDTO;
use Application\Enums\AI\IntentType;

/**
 * Contrato para handlers de IA.
 * Cada handler processa um ou mais tipos de intent.
 */
interface AIHandlerInterface
{
    /**
     * Processa a requisição de IA e retorna resposta padronizada.
     */
    public function handle(AIRequestDTO $request): AIResponseDTO;

    /**
     * Verifica se este handler suporta o intent informado.
     */
    public function supports(IntentType $intent): bool;
}
