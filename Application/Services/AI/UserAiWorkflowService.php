<?php

declare(strict_types=1);

namespace Application\Services\AI;

use Application\DTO\AI\AIRequestDTO;
use Application\DTO\AI\AIResponseDTO;
use Application\Enums\AI\AIChannel;
use Application\Enums\AI\IntentType;
use Application\Models\AiChatMessage;
use Application\Models\AiConversation;
use Application\Models\PendingAiAction;
use Application\Services\AI\Context\UserContextBuilder;
use Application\Services\AI\Media\MediaAsset;
use Application\Services\AI\Media\MediaRouterService;
use Application\Services\AI\Media\MediaType;
use Application\Services\AI\Media\ReceiptAnalysisResult;
use DomainException;
use InvalidArgumentException;

class UserAiWorkflowService
{
    private ?AIService $aiService;
    private ?UserContextBuilder $contextBuilder;
    private ?MediaRouterService $mediaRouterService;

    public function __construct(
        ?AIService $aiService = null,
        ?UserContextBuilder $contextBuilder = null,
        ?MediaRouterService $mediaRouterService = null
    ) {
        $this->aiService = $aiService;
        $this->contextBuilder = $contextBuilder;
        $this->mediaRouterService = $mediaRouterService;
    }

    /**
     * @param array<string, mixed>|null $attachment
     * @return array{response:AIResponseDTO,derived_message:?string}
     */
    public function chat(int $userId, string $message, ?array $attachment = null): array
    {
        $input = $this->prepareIncomingMessage($message, $attachment);
        $context = $this->buildCompressedUserContext($userId, $input['message']);

        $response = $this->dispatchAI(new AIRequestDTO(
            userId: $userId,
            message: $input['message'],
            context: $context,
            channel: AIChannel::WEB,
        ));

        return [
            'response' => $response,
            'derived_message' => $input['derived_message'],
        ];
    }

    public function suggestCategory(int $userId, string $description): AIResponseDTO
    {
        $description = trim($description);
        if (mb_strlen($description) < 2) {
            throw new InvalidArgumentException('Descricao muito curta para sugerir categoria.');
        }

        return $this->dispatchAI(AIRequestDTO::categorize($userId, $description));
    }

    public function analyze(int $userId, string $period): AIResponseDTO
    {
        $period = trim($period);

        return $this->dispatchAI(AIRequestDTO::analyze(
            $userId,
            $this->buildUserContext($userId),
            $period !== '' ? $period : 'ultimo mes'
        ));
    }

    public function extractTransaction(int $userId, string $message): AIResponseDTO
    {
        $message = trim($message);
        if (mb_strlen($message) < 3) {
            throw new InvalidArgumentException('Mensagem muito curta.');
        }

        return $this->dispatchAI(new AIRequestDTO(
            userId: $userId,
            message: $message,
            intent: IntentType::EXTRACT_TRANSACTION,
            channel: AIChannel::WEB,
        ));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listConversations(int $userId): array
    {
        return AiConversation::where('user_id', $userId)
            ->orderBy('updated_at', 'desc')
            ->limit(50)
            ->get(['id', 'titulo', 'created_at', 'updated_at'])
            ->toArray();
    }

    public function createConversation(int $userId): AiConversation
    {
        return AiConversation::create([
            'user_id' => $userId,
            'titulo' => null,
        ]);
    }

    public function findConversation(int $userId, int $conversationId): ?AiConversation
    {
        return AiConversation::where('id', $conversationId)
            ->where('user_id', $userId)
            ->first();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getConversationMessages(AiConversation $conversation): array
    {
        return $conversation->messages()
            ->orderBy('created_at')
            ->get(['id', 'role', 'content', 'intent', 'created_at'])
            ->toArray();
    }

    /**
     * @param array<string, mixed>|null $attachment
     * @return array<string, mixed>
     */
    public function sendConversationMessage(
        AiConversation $conversation,
        int $userId,
        string $message,
        ?array $attachment = null
    ): array {
        $input = $this->prepareIncomingMessage($message, $attachment);

        $userMessage = AiChatMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $input['message'],
        ]);

        $context = $this->buildCompressedUserContext($userId, $input['message']);
        $context['conversation_history'] = $this->buildConversationHistory($conversation);
        $context['_user_mode'] = true;
        $context['conversation_id'] = $conversation->id;

        $response = $this->dispatchAI(new AIRequestDTO(
            userId: $userId,
            message: $input['message'],
            context: $context,
            channel: AIChannel::WEB,
        ));

        $assistantMessage = AiChatMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => $response->message,
            'tokens_used' => $response->tokensUsed ?: null,
            'intent' => $response->intent?->value,
        ]);

        if ($conversation->titulo === null) {
            $conversation->titulo = mb_substr($input['message'], 0, 80);
            $conversation->save();
        }

        $conversation->touch();

        return [
            'response' => $response,
            'derived_message' => $input['derived_message'],
            'user_message' => [
                'id' => $userMessage->id,
                'role' => 'user',
                'content' => $userMessage->content,
                'created_at' => $userMessage->created_at?->toISOString(),
            ],
            'assistant_message' => [
                'id' => $assistantMessage->id,
                'role' => 'assistant',
                'content' => $assistantMessage->content,
                'intent' => $assistantMessage->intent,
                'created_at' => $assistantMessage->created_at?->toISOString(),
            ],
        ];
    }

    public function findPendingAction(int $userId, int $actionId): ?PendingAiAction
    {
        return PendingAiAction::where('id', $actionId)
            ->where('user_id', $userId)
            ->where('status', 'awaiting_confirm')
            ->first();
    }

    public function confirmPendingAction(PendingAiAction $pending, int $userId, array $input = []): AIResponseDTO
    {
        if ($pending->isExpired()) {
            $pending->markExpired();
            throw new DomainException('Acao expirada. Inicie o processo novamente.', 410);
        }

        $payload = $pending->payload;
        $changed = false;

        $contaId = isset($input['conta_id']) ? (int) $input['conta_id'] : null;
        if ($contaId !== null && $contaId > 0) {
            $payload['conta_id'] = $contaId;
            $changed = true;
        }

        $categoriaId = isset($input['categoria_id']) ? (int) $input['categoria_id'] : null;
        if ($categoriaId !== null && $categoriaId > 0) {
            $payload['categoria_id'] = $categoriaId;
            $changed = true;
        }

        if ($changed) {
            $pending->payload = $payload;
            $pending->save();
        }

        return $this->dispatchAI(new AIRequestDTO(
            userId: $userId,
            message: 'sim',
            intent: IntentType::CONFIRM_ACTION,
            channel: AIChannel::WEB,
        ));
    }

    public function rejectPendingAction(PendingAiAction $pending): void
    {
        $pending->reject();
    }

    public function deleteConversation(AiConversation $conversation): void
    {
        $conversation->delete();
    }

    /**
     * @param array<string, mixed>|null $attachment
     * @return array{message:string,derived_message:?string}
     */
    private function prepareIncomingMessage(string $message, ?array $attachment = null): array
    {
        $resolved = $this->resolveIncomingMessage($message, $attachment);
        if (!$resolved['success']) {
            throw new InvalidArgumentException($resolved['error'] ?? 'Nao consegui processar o anexo.');
        }

        $finalMessage = trim((string) $resolved['message']);
        if ($finalMessage === '') {
            throw new InvalidArgumentException('Mensagem não pode ser vazia.');
        }

        if (mb_strlen($finalMessage) > 2000) {
            throw new InvalidArgumentException('Mensagem muito longa (máximo 2000 caracteres).');
        }

        return [
            'message' => $finalMessage,
            'derived_message' => $resolved['derived_message'],
        ];
    }

    /**
     * @param array<string, mixed>|null $attachment
     * @return array{success:bool,message:string,derived_message:?string,error?:string}
     */
    private function resolveIncomingMessage(string $message, ?array $attachment = null): array
    {
        $message = trim($message);

        if ($attachment === null) {
            return [
                'success' => true,
                'message' => $message,
                'derived_message' => null,
            ];
        }

        $tmpName = (string) ($attachment['tmp_name'] ?? '');
        if ($tmpName === '' || !is_file($tmpName)) {
            return [
                'success' => false,
                'message' => $message,
                'derived_message' => null,
                'error' => 'Arquivo enviado e invalido ou nao pode ser lido.',
            ];
        }

        $content = file_get_contents($tmpName);
        if (!is_string($content) || $content === '') {
            return [
                'success' => false,
                'message' => $message,
                'derived_message' => null,
                'error' => 'Nao consegui ler o arquivo enviado.',
            ];
        }

        $asset = new MediaAsset(
            sourceType: 'document',
            content: $content,
            mimeType: $attachment['type'] ?? null,
            filename: $attachment['name'] ?? null,
            fileSize: isset($attachment['size']) ? (int) $attachment['size'] : null,
            caption: $message !== '' ? $message : null,
        );

        if ($asset->mediaType() === MediaType::VIDEO) {
            return [
                'success' => false,
                'message' => $message,
                'derived_message' => null,
                'error' => 'Videos nao sao suportados. Envie imagem, PDF ou audio.',
            ];
        }

        $result = $this->mediaRouterService()->process($asset);
        if ($result->isUnsupported()) {
            return [
                'success' => false,
                'message' => $message,
                'derived_message' => null,
                'error' => $result->error ?? 'Tipo de arquivo nao suportado. Envie imagem, PDF ou audio.',
            ];
        }

        if (!$result->success) {
            return [
                'success' => false,
                'message' => $message,
                'derived_message' => null,
                'error' => $result->error ?? 'Nao consegui processar o arquivo enviado.',
            ];
        }

        $derivedMessage = $result->text;

        if ($result->isReceiptAnalysis()) {
            $receipt = new ReceiptAnalysisResult(
                success: $result->success,
                data: $result->data,
                rawText: $result->text,
                tokensUsed: $result->tokensUsed,
                error: $result->error,
            );

            if (!$receipt->isFinancial()) {
                if ($message !== '') {
                    return [
                        'success' => true,
                        'message' => $message,
                        'derived_message' => null,
                    ];
                }

                $description = $receipt->data['descricao'] ?? 'Nao identifiquei informacoes financeiras nesse arquivo.';

                return [
                    'success' => false,
                    'message' => '',
                    'derived_message' => null,
                    'error' => $description,
                ];
            }

            $derivedMessage = $receipt->toTransactionText();
        }

        $finalMessage = trim($message !== '' && $derivedMessage !== ''
            ? "{$message}\n{$derivedMessage}"
            : ($derivedMessage !== '' ? $derivedMessage : $message));

        return [
            'success' => true,
            'message' => $finalMessage,
            'derived_message' => $derivedMessage !== '' ? $derivedMessage : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildUserContext(int $userId): array
    {
        try {
            return $this->contextBuilder()->build($userId);
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function buildCompressedUserContext(int $userId, string $message): array
    {
        return ContextCompressor::compress($this->buildUserContext($userId), $message);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildConversationHistory(AiConversation $conversation): array
    {
        return $conversation->messages()
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get(['role', 'content'])
            ->reverse()
            ->values()
            ->map(function ($message) {
                $item = $message->toArray();

                if (($item['role'] ?? '') === 'assistant' && mb_strlen($item['content'] ?? '') > 300) {
                    $item['content'] = mb_substr($item['content'], 0, 300) . '...';
                }

                return $item;
            })
            ->toArray();
    }

    private function dispatchAI(AIRequestDTO $request): AIResponseDTO
    {
        return $this->aiService()->dispatch($request);
    }

    private function aiService(): AIService
    {
        return $this->aiService ??= new AIService();
    }

    private function contextBuilder(): UserContextBuilder
    {
        return $this->contextBuilder ??= new UserContextBuilder();
    }

    private function mediaRouterService(): MediaRouterService
    {
        return $this->mediaRouterService ??= new MediaRouterService();
    }
}
