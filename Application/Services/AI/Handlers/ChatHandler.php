<?php

declare(strict_types=1);

namespace Application\Services\AI\Handlers;

use Application\DTO\AI\AIRequestDTO;
use Application\DTO\AI\AIResponseDTO;
use Application\Enums\AI\IntentType;
use Application\Services\AI\AIService;
use Application\Services\AI\ContextCompressor;
use Application\Services\AI\PromptBuilder;
use Application\Services\AI\PromptOptimizer;

/**
 * Handler para conversas gerais com o assistente de IA.
 * Sempre chama o LLM — é o handler de fallback padrão.
 */
class ChatHandler implements AIHandlerInterface
{
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

            // Selecionar prompt baseado no canal
            $systemPrompt = $request->isAdmin()
                ? PromptBuilder::chatSystem($context)
                : PromptBuilder::userChatSystem($context);

            // Chamar LLM via AIService
            $ai = new AIService();
            $response = $ai->chat($request->message, $context);

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
