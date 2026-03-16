<?php

declare(strict_types=1);

namespace Application\Services\AI;

use Application\DTO\AI\AIRequestDTO;
use Application\DTO\AI\AIResponseDTO;
use Application\Enums\AI\AIChannel;
use Application\Models\AiChatMessage;
use Application\Models\AiConversation;
use Application\Services\AI\Context\UserContextBuilder;

/**
 * Orquestra o ciclo de conversa textual para canais externos.
 *
 * Mantém o comportamento compartilhado entre Telegram e WhatsApp:
 * - reutiliza conversa recente do canal
 * - salva histórico
 * - injeta contexto financeiro e histórico curto
 * - despacha para a pipeline unificada da IA
 */
class ChannelConversationService
{
    /**
     * @param array<string, mixed> $baseContext
     * @return array{conversation: AiConversation, response: AIResponseDTO}
     */
    public function processTextTurn(
        int $userId,
        string $message,
        AIChannel $channel,
        string $conversationTitle,
        array $baseContext = [],
    ): array {
        $conversation = $this->getOrCreateConversation($userId, $conversationTitle);

        AiChatMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $message,
        ]);

        $context = $this->buildContext($userId, $message, $conversation, $baseContext);

        $response = (new AIService())->dispatch(new AIRequestDTO(
            userId: $userId,
            message: $message,
            context: $context,
            channel: $channel,
        ));

        AiChatMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => $response->message,
            'tokens_used' => $response->tokensUsed ?: null,
            'intent' => $response->intent?->value,
        ]);

        $conversation->touch();

        return [
            'conversation' => $conversation,
            'response' => $response,
        ];
    }

    public function getOrCreateConversation(int $userId, string $title, int $reuseHours = 24): AiConversation
    {
        $conversation = AiConversation::where('user_id', $userId)
            ->where('titulo', $title)
            ->where('updated_at', '>=', now()->subHours($reuseHours))
            ->orderByDesc('updated_at')
            ->first();

        if ($conversation !== null) {
            return $conversation;
        }

        return AiConversation::create([
            'user_id' => $userId,
            'titulo' => $title,
        ]);
    }

    /**
     * @param array<string, mixed> $baseContext
     * @return array<string, mixed>
     */
    private function buildContext(int $userId, string $message, AiConversation $conversation, array $baseContext): array
    {
        try {
            $context = (new UserContextBuilder())->build($userId);
        } catch (\Throwable) {
            $context = [];
        }

        $context = ContextCompressor::compress($context, $message);
        $context = array_merge($context, $baseContext);

        $history = $conversation->messages()
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get(['role', 'content'])
            ->reverse()
            ->values()
            ->map(function ($msg) {
                $item = $msg->toArray();
                if (($item['role'] ?? '') === 'assistant' && mb_strlen($item['content'] ?? '') > 300) {
                    $item['content'] = mb_substr($item['content'], 0, 300) . '…';
                }
                return $item;
            })
            ->toArray();

        $context['conversation_history'] = $history;
        $context['_user_mode'] = true;
        $context['conversation_id'] = $conversation->id;

        return $context;
    }
}
