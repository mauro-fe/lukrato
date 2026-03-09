<?php

declare(strict_types=1);

namespace Application\Services\AI\Handlers;

use Application\DTO\AI\AIRequestDTO;
use Application\DTO\AI\AIResponseDTO;
use Application\Enums\AI\IntentType;
use Application\Services\AI\Contracts\AIProvider;
use Application\Services\AI\ContextCompressor;
use Application\Services\AI\PromptBuilder;
use Application\Services\AI\PromptOptimizer;

/**
 * Handler para conversas gerais com o assistente de IA.
 * Sempre chama o LLM — é o handler de fallback padrão.
 */
class ChatHandler implements AIHandlerInterface
{
    private ?AIProvider $provider = null;

    public function setProvider(AIProvider $provider): void
    {
        $this->provider = $provider;
    }

    public function supports(IntentType $intent): bool
    {
        return $intent === IntentType::CHAT;
    }

    public function handle(AIRequestDTO $request): AIResponseDTO
    {
        try {
            // Comprimir contexto baseado na mensagem
            $context = $request->context;
            if (!empty($context)) {
                $context = ContextCompressor::compress($context, $request->message);
                $context = PromptOptimizer::optimize($context);
            }

            // Chamar LLM via provider
            $response = $this->provider->chat($request->message, $context);

            // Checar se é resposta de fallback
            if (str_contains($response, 'indisponível no momento')) {
                return AIResponseDTO::fail($response, IntentType::CHAT);
            }

            return AIResponseDTO::fromLLM(
                $response,
                ['response' => $response],
                IntentType::CHAT,
            );
        } catch (\Throwable $e) {
            return AIResponseDTO::fail(
                'O assistente de IA está indisponível no momento. Tente novamente em instantes.',
                IntentType::CHAT,
            );
        }
    }
}
