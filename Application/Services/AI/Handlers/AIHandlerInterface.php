<?php

declare(strict_types=1);

namespace Application\Services\AI\Handlers;

use Application\DTO\AI\AIRequestDTO;
use Application\DTO\AI\AIResponseDTO;
use Application\Enums\AI\IntentType;
use Application\Services\AI\Contracts\AIProvider;

/**
 * Contrato para handlers de IA.
 * Cada handler processa um ou mais tipos de intent.
 *
 * Os handlers recebem o AIProvider via setProvider() após construção,
 * evitando instanciação circular com AIService.
 */
interface AIHandlerInterface
{
    /**
     * Injeta o provider de IA no handler.
     * Chamado automaticamente pelo AIService após registro.
     */
    public function setProvider(AIProvider $provider): void;

    /**
     * Processa a requisição de IA e retorna resposta padronizada.
     */
    public function handle(AIRequestDTO $request): AIResponseDTO;

    /**
     * Verifica se este handler suporta o intent informado.
     */
    public function supports(IntentType $intent): bool;
}
