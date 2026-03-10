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
 * Mensagens triviais (saudações, testes) são respondidas sem LLM (0 tokens).
 */
class ChatHandler implements AIHandlerInterface
{
    private ?AIProvider $provider = null;

    /**
     * Padrões de mensagens triviais que podem ser respondidas sem LLM.
     * Formato: pattern => resposta template
     */
    private const TRIVIAL_PATTERNS = [
        'oi|olá|ola|hey|eai|e ai|fala|bom dia|boa tarde|boa noite|hello|hi\b' =>
            'Olá! 👋 Como posso ajudar você com suas finanças hoje?',
        '^teste$|^test$|^testing$|^testando$' =>
            'Teste recebido! ✅ Estou funcionando. Como posso ajudar?',
        '^ok$|^beleza$|^blz$|^valeu$|^obrigad|^thanks|^thx' =>
            'Estou aqui se precisar de mais alguma coisa! 😊',
        'quem [eé] voc[eê]|o que voc[eê] faz|como funciona' =>
            'Sou o assistente financeiro do Lukrato! 🤖 Posso te ajudar com: consultar saldos, analisar gastos, acompanhar metas e orçamentos, e dar dicas financeiras. O que gostaria de saber?',
    ];

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
        // Responder mensagens triviais sem LLM (0 tokens)
        $trivialResponse = $this->matchTrivial($request->message);
        if ($trivialResponse !== null) {
            return AIResponseDTO::fromComputed(
                $trivialResponse,
                ['source' => 'trivial'],
                IntentType::CHAT,
            );
        }

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

    /**
     * Verifica se a mensagem é trivial e retorna resposta template (0 tokens).
     */
    private function matchTrivial(string $message): ?string
    {
        $normalized = mb_strtolower(trim($message));

        // Mensagens muito curtas ou genéricas
        if (mb_strlen($normalized) > 60) {
            return null;
        }

        foreach (self::TRIVIAL_PATTERNS as $pattern => $response) {
            if (preg_match('/' . $pattern . '/iu', $normalized)) {
                return $response;
            }
        }

        return null;
    }
}
