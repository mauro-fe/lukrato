<?php

declare(strict_types=1);

namespace Application\Services\AI\WhatsApp;

use Application\Config\AiRuntimeConfig;
use Application\Container\ApplicationContainer;
use Application\DTO\AI\AIResponseDTO;
use Application\DTO\AI\WhatsAppMessageDTO;
use Application\Enums\AI\AIChannel;
use Application\Enums\LogCategory;
use Application\Enums\LogLevel;
use Application\Models\AiConversation;
use Application\Models\PendingAiAction;
use Application\Models\Usuario;
use Application\Models\WhatsAppMessage;
use Application\Repositories\ContaRepository;
use Application\Services\AI\AIQuotaService;
use Application\Services\AI\Actions\ActionRegistry;
use Application\Services\AI\AiLogService;
use Application\Services\AI\ChannelConversationService;
use Application\Services\AI\ConversationStateService;
use Application\Services\AI\Media\MediaAsset;
use Application\Services\AI\Media\MediaProcessingResult;
use Application\Services\AI\Media\MediaRouterService;
use Application\Services\AI\Media\ReceiptAnalysisResult;
use Application\Services\AI\NLP\NumberNormalizer;
use Application\Services\AI\NLP\TextNormalizer;
use Application\Services\AI\Rules\CategoryRuleEngine;
use Application\Services\AI\Security\AIRateLimiter;
use Application\Services\Infrastructure\CacheService;
use Application\Services\Infrastructure\LogService;
use Illuminate\Database\Capsule\Manager as DB;

class WhatsAppWebhookWorkflowService
{
    private const SENDER_LIMIT = 30;
    private const SENDER_WINDOW_SECONDS = 60;
    private const QUICK_REPLY_TTL_SECONDS = 900;
    private const MAX_QUICK_REPLIES = 3;
    private const CONVERSATION_TITLE = 'WhatsApp';

    private WhatsAppService $whatsapp;
    private ChannelConversationService $conversationService;
    private MediaRouterService $mediaRouterService;
    private ContaRepository $contaRepository;
    private CacheService $cache;
    private ActionRegistry $actionRegistry;
    private AIRateLimiter $rateLimiter;
    private ?AiRuntimeConfig $aiRuntimeConfig = null;

    public function __construct(
        ?WhatsAppService $whatsapp = null,
        ?ChannelConversationService $conversationService = null,
        ?MediaRouterService $mediaRouterService = null,
        ?ContaRepository $contaRepository = null,
        ?CacheService $cache = null,
        ?ActionRegistry $actionRegistry = null,
        ?AIRateLimiter $rateLimiter = null
    ) {
        $this->whatsapp = ApplicationContainer::resolveOrNew($whatsapp, WhatsAppService::class);
        $this->conversationService = ApplicationContainer::resolveOrNew($conversationService, ChannelConversationService::class);
        $this->mediaRouterService = ApplicationContainer::resolveOrNew($mediaRouterService, MediaRouterService::class);
        $this->contaRepository = ApplicationContainer::resolveOrNew($contaRepository, ContaRepository::class);
        $this->cache = ApplicationContainer::resolveOrNew($cache, CacheService::class);
        $this->actionRegistry = ApplicationContainer::resolveOrNew($actionRegistry, ActionRegistry::class);
        $this->rateLimiter = ApplicationContainer::resolveOrNew($rateLimiter, AIRateLimiter::class);
    }

    public function handleWebhookBody(string $rawBody): void
    {
        $payload = json_decode($rawBody, true);

        if (!is_array($payload)) {
            return;
        }

        $entry = $payload['entry'][0]['changes'][0]['value'] ?? null;

        if ($entry === null || isset($entry['statuses'])) {
            return;
        }

        $dto = WhatsAppMessageDTO::fromMetaPayload($entry);

        if ($dto === null || WhatsAppMessage::alreadyProcessed($dto->waMessageId)) {
            return;
        }

        $msgRecord = WhatsAppMessage::create([
            'wa_message_id' => $dto->waMessageId,
            'from_phone' => $dto->fromPhone,
            'direction' => 'incoming',
            'type' => $dto->type,
            'body' => $dto->body,
            'metadata' => $dto->rawPayload,
            'processing_status' => 'received',
            'media_file_id' => $dto->mediaId,
            'media_mime_type' => $dto->mimeType,
            'media_file_size' => $dto->fileSize,
            'media_filename' => $dto->filename,
        ]);

        try {
            $this->processMessage($dto, $msgRecord);
        } catch (\Throwable $e) {
            $msgRecord->markFailed($e->getMessage());

            LogService::persist(
                LogLevel::ERROR,
                LogCategory::WEBHOOK,
                'Erro ao processar mensagem WhatsApp',
                [
                    'wa_message_id' => $dto->waMessageId,
                    'phone' => $dto->fromPhone,
                    'error' => $e->getMessage(),
                ],
            );
        }
    }

    private function processMessage(WhatsAppMessageDTO $dto, WhatsAppMessage $msgRecord): void
    {
        if (!$this->allowIncomingSender($dto->fromPhone, $dto->waMessageId)) {
            $msgRecord->markIgnored();
            return;
        }

        $this->whatsapp()->markAsRead($dto->waMessageId);

        $user = WhatsAppUserResolver::resolve($dto->fromPhone);

        if ($user === null) {
            $this->whatsapp()->sendText(
                $dto->fromPhone,
                "Olá! Você ainda não vinculou seu WhatsApp ao Lukrato.\n\n"
                    . "Acesse seu painel em lukrato.com.br → Configurações → WhatsApp para vincular."
            );
            $msgRecord->markIgnored();
            return;
        }

        $msgRecord->update(['user_id' => $user->id]);

        if ($dto->isMedia()) {
            $this->handleMediaMessage($dto, $user, $msgRecord);
            return;
        }

        if ($this->shouldHandleConfirmationReply($dto, $user->id)) {
            $this->handleConfirmationReply($dto, $user, $msgRecord);
            return;
        }

        $this->handleNormalMessage($dto, $user, $msgRecord);
    }

    private function handleConfirmationReply(WhatsAppMessageDTO $dto, Usuario $user, WhatsAppMessage $msgRecord): void
    {
        DB::transaction(function () use ($dto, $user, $msgRecord) {
            $pending = PendingAiAction::query()
                ->where('user_id', $user->id)
                ->awaiting()
                ->orderByDesc('created_at')
                ->lockForUpdate()
                ->first();

            if ($pending === null) {
                $this->whatsapp()->sendText(
                    $dto->fromPhone,
                    "Não encontrei nenhuma transação pendente de confirmação."
                );
                $msgRecord->markProcessed('confirmation_no_pending');
                return;
            }

            if ($dto->isAffirmative()) {
                $action = $this->actionRegistry->resolve($pending->action_type);

                if ($action === null) {
                    $pending->reject();
                    $this->whatsapp()->sendText($dto->fromPhone, "⚠️ Tipo de ação desconhecida.");
                    $msgRecord->markProcessed('confirmation_unknown_action');
                    return;
                }

                $payload = $pending->payload;

                if ($pending->action_type === 'create_lancamento' && empty($payload['conta_id'])) {
                    $contas = $this->contaRepository->findActive($user->id);

                    if ($contas->isEmpty()) {
                        $pending->reject();
                        $this->whatsapp()->sendText(
                            $dto->fromPhone,
                            "⚠️ Você precisa ter pelo menos uma conta cadastrada no Lukrato para registrar lançamentos."
                        );
                        $msgRecord->markProcessed('confirmation_no_account');
                        return;
                    }

                    $selectedConta = $contas->first();
                    $nomeCartao = mb_strtolower($payload['nome_cartao'] ?? '');
                    if ($nomeCartao !== '' && $contas->count() > 1) {
                        foreach ($contas as $conta) {
                            if (str_contains(mb_strtolower($conta->nome), $nomeCartao)) {
                                $selectedConta = $conta;
                                break;
                            }
                        }
                    }

                    $payload['conta_id'] = $selectedConta->id;
                    $pending->update(['payload' => $payload]);
                }

                try {
                    $result = $action->execute($user->id, $payload);

                    if (!$result->success) {
                        $pending->reject();
                        $this->whatsapp()->sendText($dto->fromPhone, "⚠️ {$result->message}");
                        $msgRecord->markProcessed('confirmation_failed');
                        return;
                    }

                    $pending->confirm();

                    if (!empty($payload['descricao']) && !empty($payload['categoria_id'])) {
                        CategoryRuleEngine::learn(
                            $user->id,
                            $payload['descricao'],
                            (int) $payload['categoria_id'],
                            !empty($payload['subcategoria_id']) ? (int) $payload['subcategoria_id'] : null,
                            'confirmed'
                        );
                    }

                    $formatted = 'R$ ' . number_format((float) ($payload['valor'] ?? 0), 2, ',', '.');
                    $catStr = !empty($payload['categoria_nome']) ? "\n📁 {$payload['categoria_nome']}" : '';
                    $this->whatsapp()->sendText(
                        $dto->fromPhone,
                        "✅ Lançamento registrado!\n\n"
                            . "📝 {$payload['descricao']}\n"
                            . "💰 {$formatted}{$catStr}"
                    );
                    $msgRecord->markProcessed('transaction_confirmed');
                } catch (\Throwable $e) {
                    $pending->reject();
                    $this->whatsapp()->sendText($dto->fromPhone, "⚠️ Erro ao registrar: " . $e->getMessage());
                    $msgRecord->markProcessed('confirmation_error');
                }

                return;
            }

            $pending->reject();
            $this->whatsapp()->sendText($dto->fromPhone, "❌ Transação cancelada.");
            $msgRecord->markProcessed('transaction_rejected');
        });
    }

    private function shouldHandleConfirmationReply(WhatsAppMessageDTO $dto, int $userId): bool
    {
        if (!$dto->isAffirmative() && !$dto->isNegative()) {
            return false;
        }

        return PendingAiAction::where('user_id', $userId)
            ->awaiting()
            ->exists();
    }
    private function handleMediaMessage(WhatsAppMessageDTO $dto, Usuario $user, WhatsAppMessage $msgRecord): void
    {
        if ($dto->isVideo()) {
            $this->whatsapp()->sendText(
                $dto->fromPhone,
                'Videos não são suportados. Envie imagem, PDF ou áudio.'
            );
            $msgRecord->markProcessed('video_not_supported');
            return;
        }

        if (!AIQuotaService::hasQuotaRemaining($user, 'chat')) {
            $usage = AIQuotaService::getUsage($user);
            $limit = $usage['chat']['limit'] ?? 5;
            $this->whatsapp()->sendText(
                $dto->fromPhone,
                "Olá! Você usou suas {$limit} mensagens de IA gratuitas este mês. "
                    . "Faça upgrade para o Pro: https://lukrato.com.br/billing"
            );
            $msgRecord->markProcessed('quota_exceeded');
            return;
        }

        $statusText = $dto->isAudio()
            ? "Transcrevendo audio..."
            : "Analisando arquivo...";
        $this->whatsapp()->sendText($dto->fromPhone, $statusText);

        $downloader = new WhatsAppMediaDownloader();
        $fileData = $downloader->downloadByMediaId((string) $dto->mediaId, $dto->filename);

        if ($fileData === null) {
            $this->whatsapp()->sendText($dto->fromPhone, "⚠️ Não consegui baixar o arquivo. Tente novamente.");
            $msgRecord->markFailed('file_download_failed');
            return;
        }

        $asset = new MediaAsset(
            sourceType: $dto->type,
            content: $fileData['content'],
            mimeType: $dto->mimeType ?? $fileData['mime_type'],
            filename: $dto->filename ?? $fileData['filename'],
            fileSize: $dto->fileSize ?? $fileData['file_size'],
            caption: $dto->caption ?? $dto->body,
            remoteId: $dto->mediaId,
        );

        $result = $this->mediaRouterService->process($asset);
        $this->logMediaProcessing($user->id, 'whatsapp', $dto->mediaId, $dto->caption, $result);

        if ($result->isUnsupported()) {
            $this->whatsapp()->sendText(
                $dto->fromPhone,
                $result->error ?? 'Tipo de arquivo nao suportado. Envie imagem, PDF ou audio.'
            );
            $msgRecord->markProcessed('unsupported_media');
            return;
        }

        if (!$result->success) {
            $this->whatsapp()->sendText(
                $dto->fromPhone,
                "⚠️ Não consegui processar o arquivo. " . ($result->error ?? 'Tente novamente ou envie em outro formato.')
            );
            $msgRecord->markFailed('media_processing_failed: ' . ($result->error ?? 'unknown'));
            return;
        }

        if ($result->isReceiptAnalysis()) {
            $receipt = new ReceiptAnalysisResult(
                success: $result->success,
                data: $result->data,
                rawText: $result->text,
                tokensUsed: $result->tokensUsed,
                error: $result->error,
            );

            $msgRecord->update([
                'transcription' => json_encode($receipt->data, JSON_UNESCAPED_UNICODE),
                'body' => $receipt->toTransactionText(),
            ]);

            if (!$receipt->isFinancial()) {
                $desc = $receipt->data['descricao'] ?? 'Nao identifiquei informacoes financeiras nesse arquivo.';
                $this->whatsapp()->sendText(
                    $dto->fromPhone,
                    "📎 {$desc}\n\nPara registrar uma transação, envie um comprovante, nota fiscal, PDF ou descreva o lançamento por texto."
                );
                $msgRecord->markProcessed('media_not_financial');
                return;
            }

            $transactionData = $receipt->toTransactionData();
            if ($transactionData['valor'] <= 0) {
                $desc = $transactionData['descricao'];
                $this->whatsapp()->sendText(
                    $dto->fromPhone,
                    "📎 Vi um comprovante de {$desc}, mas não consegui identificar o valor. "
                        . "Pode digitar? Ex: \"{$desc} 35.50\""
                );
                $msgRecord->markProcessed('media_no_amount');
                return;
            }

            $this->handleTransactionExtraction($dto, $user, $transactionData, $msgRecord);
            return;
        }

        $msgRecord->update([
            'transcription' => $result->text,
            'body' => $result->text,
        ]);

        $textDto = new WhatsAppMessageDTO(
            waMessageId: $dto->waMessageId,
            fromPhone: $dto->fromPhone,
            type: 'text',
            body: $result->text,
            displayName: $dto->displayName,
            rawPayload: $dto->rawPayload,
        );

        $this->handleNormalMessage($textDto, $user, $msgRecord);
    }

    private function logMediaProcessing(
        int $userId,
        string $channel,
        ?string $fileId,
        ?string $caption,
        MediaProcessingResult $result
    ): void {
        $logType = in_array($result->operation, ['receipt_analysis', 'document_analysis'], true)
            ? 'image_analysis'
            : 'audio_transcription';

        AiLogService::log([
            'user_id' => $userId,
            'type' => $logType,
            'channel' => $channel,
            'prompt' => "[media:{$fileId}]" . ($caption ? " caption: {$caption}" : ''),
            'response' => $result->text !== '' ? $result->text : json_encode($result->data, JSON_UNESCAPED_UNICODE),
            'provider' => 'openai',
            'model' => $this->aiRuntimeConfig()->mediaLogModel(),
            'tokens_prompt' => $result->tokensPrompt,
            'tokens_completion' => $result->tokensCompletion,
            'tokens_total' => $result->tokensUsed > 0
                ? $result->tokensUsed
                : ($result->tokensPrompt + $result->tokensCompletion),
            'response_time_ms' => $result->durationMs,
            'success' => $result->success,
            'error_message' => $result->error,
        ]);
    }

    private function aiRuntimeConfig(): AiRuntimeConfig
    {
        return $this->aiRuntimeConfig ??= ApplicationContainer::resolveOrNew(null, AiRuntimeConfig::class);
    }

    private function handleNormalMessage(WhatsAppMessageDTO $dto, Usuario $user, WhatsAppMessage $msgRecord): void
    {
        $normalizedBody = TextNormalizer::normalize($dto->body);
        $normalizedBody = NumberNormalizer::normalize($normalizedBody);

        if ($dto->isQuickReplySelection()) {
            $this->handleQuickReplySelection($dto, $user, $msgRecord);
            return;
        }

        if ($dto->isOptionSelection()) {
            $this->handleOptionSelection($dto, $user, $msgRecord);
            return;
        }

        if ($this->handleLocalShortcut($dto, $user, $msgRecord, $normalizedBody)) {
            return;
        }


        if (!AIQuotaService::hasQuotaRemaining($user, 'chat')) {
            $usage = AIQuotaService::getUsage($user);
            $limit = $usage['chat']['limit'] ?? 5;
            $this->whatsapp()->sendText(
                $dto->fromPhone,
                "Olá, você usou suas {$limit} mensagens de IA gratuitas este mês. "
                    . "Faça upgrade para o Pro e tenha IA ilimitada: https://lukrato.com.br/billing"
            );
            $msgRecord->markProcessed('quota_exceeded');
            return;
        }

        $result = $this->conversationService()->processTextTurn(
            $user->id,
            $normalizedBody,
            AIChannel::WHATSAPP,
            self::CONVERSATION_TITLE,
            ['from_phone' => $dto->fromPhone],
        );

        $this->sendAiResponse($dto->fromPhone, $result['response'], $msgRecord);
    }

    /**
     * @param array<string, mixed> $extracted
     */
    private function handleTransactionExtraction(
        WhatsAppMessageDTO $dto,
        Usuario $user,
        array $extracted,
        WhatsAppMessage $msgRecord
    ): void {
        $category = CategoryRuleEngine::match(
            $extracted['descricao'],
            $user->id,
            $extracted['categoria_contexto'] ?? null
        );

        $payload = [
            'descricao' => $extracted['descricao'],
            'valor' => $extracted['valor'],
            'tipo' => $extracted['tipo'],
            'data' => $extracted['data'],
            'categoria_id' => $category['categoria_id'] ?? null,
            'subcategoria_id' => $category['subcategoria_id'] ?? null,
            'categoria_nome' => $category['categoria'] ?? null,
            'subcategoria_nome' => $category['subcategoria'] ?? null,
            'origem' => 'whatsapp',
            'pago' => true,
        ];

        if (!empty($extracted['forma_pagamento'])) {
            $payload['forma_pagamento'] = $extracted['forma_pagamento'];
        }
        if (!empty($extracted['eh_parcelado'])) {
            $payload['eh_parcelado'] = $extracted['eh_parcelado'];
            $payload['total_parcelas'] = $extracted['total_parcelas'] ?? null;
        }
        if (!empty($extracted['nome_cartao'])) {
            $payload['nome_cartao'] = $extracted['nome_cartao'];
        }

        AiLogService::log([
            'user_id' => $user->id,
            'type' => 'transaction_preview',
            'channel' => 'whatsapp',
            'prompt' => $dto->body,
            'response' => json_encode([
                'extracted' => $extracted,
                'category' => $category,
                'payload' => $payload,
            ], JSON_UNESCAPED_UNICODE),
            'provider' => 'internal',
            'model' => 'regex+rules',
            'tokens_prompt' => 0,
            'tokens_completion' => 0,
            'tokens_total' => 0,
            'response_time_ms' => 0,
            'success' => true,
            'source' => 'rule',
            'confidence' => 1.0,
            'prompt_version' => 'whatsapp_tx_preview_v1',
        ]);

        PendingAiAction::create([
            'user_id' => $user->id,
            'action_type' => 'create_lancamento',
            'payload' => $payload,
            'status' => 'awaiting_confirm',
            'expires_at' => now()->addHours(24),
        ]);

        $tipo = $extracted['tipo'] === 'receita' ? '💰 Receita' : '💸 Despesa';
        $formatted = 'R$ ' . number_format($extracted['valor'], 2, ',', '.');
        $catStr = '';
        if ($category !== null) {
            $catStr = "\n📁 " . $category['categoria'];
            if (!empty($category['subcategoria'])) {
                $catStr .= ' > ' . $category['subcategoria'];
            }
        }

        $text = "Entendi! Registrar?\n\n"
            . "{$tipo}: {$extracted['descricao']}\n"
            . "💵 {$formatted}{$catStr}\n\n"
            . "Responda Sim para confirmar ou Não para cancelar.";

        $this->whatsapp()->sendConfirmationButtons(
            $dto->fromPhone,
            $text,
            'confirm_yes',
            'confirm_no',
        );

        $msgRecord->markProcessed('transaction_pending');
    }
    private function handleQuickReplySelection(WhatsAppMessageDTO $dto, Usuario $user, WhatsAppMessage $msgRecord): void
    {
        $replyIndex = $dto->getSelectedQuickReplyIndex();
        $quickReplies = $this->getCachedQuickReplies($dto->fromPhone);
        $message = $replyIndex !== null
            ? trim((string) ($quickReplies[$replyIndex]['message'] ?? ''))
            : '';

        if (
            $replyIndex === null
            || !isset($quickReplies[$replyIndex]['message'])
            || $message === ''
        ) {
            $this->whatsapp()->sendText(
                $dto->fromPhone,
                'Esse atalho expirou. Me envie a mensagem manualmente ou escolha outro caminho.'
            );
            $msgRecord->markProcessed('quick_reply_expired');
            return;
        }

        $textDto = new WhatsAppMessageDTO(
            waMessageId: $dto->waMessageId,
            fromPhone: $dto->fromPhone,
            type: 'text',
            body: $message,
            displayName: $dto->displayName,
            rawPayload: $dto->rawPayload,
        );

        $this->handleNormalMessage($textDto, $user, $msgRecord);
    }

    private function handleOptionSelection(WhatsAppMessageDTO $dto, Usuario $user, WhatsAppMessage $msgRecord): void
    {
        $optionIndex = $dto->getSelectedOptionIndex();

        if ($optionIndex === null) {
            $this->whatsapp()->sendText($dto->fromPhone, 'Opção inválida.');
            $msgRecord->markProcessed('option_selection_invalid');
            return;
        }

        $textDto = new WhatsAppMessageDTO(
            waMessageId: $dto->waMessageId,
            fromPhone: $dto->fromPhone,
            type: 'text',
            body: (string) ($optionIndex + 1),
            displayName: $dto->displayName,
            rawPayload: $dto->rawPayload,
        );

        $this->handleNormalMessage($textDto, $user, $msgRecord);
    }

    private function sendAiResponse(string $toPhone, AIResponseDTO $response, WhatsAppMessage $msgRecord): void
    {
        $intent = $response->intent->value ?? 'chat';
        $pendingId = $response->data['pending_id'] ?? $response->data['pending_action_id'] ?? null;

        if (!empty($pendingId)) {
            $this->sendConfirmationMessage($toPhone, $response->message);
            $msgRecord->markProcessed($intent . '_pending');
            return;
        }

        if (!empty($response->data['options']) && ($response->data['action'] ?? '') === 'awaiting_selection') {
            $this->sendSelectionResponse($toPhone, $response->message, $response->data['options']);
            $msgRecord->markProcessed($intent . '_awaiting_selection');
            return;
        }

        if (!empty($response->data['quick_replies']) && is_array($response->data['quick_replies'])) {
            $this->sendQuickReplyResponse(
                $toPhone,
                $response->message,
                $response->data['quick_replies'],
                $response->data['suggestion'] ?? null,
            );
            $msgRecord->markProcessed($intent . '_quick_replies');
            return;
        }

        $this->sendTextChunks(
            $toPhone,
            $this->appendSuggestion($response->message, $response->data['suggestion'] ?? null),
        );
        $msgRecord->markProcessed($intent);
    }

    private function sendConfirmationMessage(string $toPhone, string $text): void
    {
        $chunks = $this->splitMessage($text, 1024);
        $lastIndex = count($chunks) - 1;

        foreach ($chunks as $index => $chunk) {
            if ($index === $lastIndex) {
                $this->whatsapp()->sendConfirmationButtons($toPhone, $chunk);
                continue;
            }

            $this->whatsapp()->sendText($toPhone, $chunk);
        }
    }

    /**
     * @param array<int, mixed> $options
     */
    private function sendSelectionResponse(string $toPhone, string $text, array $options): void
    {
        $normalizedOptions = $this->normalizeSelectionOptions($options);
        $message = $this->buildSelectionMessage($text, $normalizedOptions);

        if (!empty($normalizedOptions) && count($normalizedOptions) <= self::MAX_QUICK_REPLIES) {
            $buttons = [];
            foreach ($normalizedOptions as $index => $option) {
                $buttons[] = [
                    'id' => "select_option_{$index}",
                    'title' => $option['label'],
                ];
            }

            $chunks = $this->splitMessage($message, 1024);
            $lastIndex = count($chunks) - 1;

            foreach ($chunks as $index => $chunk) {
                if ($index === $lastIndex) {
                    $this->whatsapp()->sendReplyButtons($toPhone, $chunk, $buttons);
                    continue;
                }

                $this->whatsapp()->sendText($toPhone, $chunk);
            }
            return;
        }

        $this->sendTextChunks($toPhone, $message);
    }

    /**
     * @param array<int, mixed> $quickReplies
     */
    private function sendQuickReplyResponse(
        string $toPhone,
        string $text,
        array $quickReplies,
        ?string $suggestion = null
    ): void {
        $normalizedReplies = $this->normalizeQuickReplies($quickReplies);
        $message = $this->appendSuggestion($text, $suggestion);

        if (empty($normalizedReplies)) {
            $this->sendTextChunks($toPhone, $message);
            return;
        }

        $this->cacheQuickReplies($toPhone, $normalizedReplies);

        $buttons = [];
        foreach ($normalizedReplies as $index => $reply) {
            $buttons[] = [
                'id' => "quick_reply_{$index}",
                'title' => $reply['label'],
            ];
        }

        $chunks = $this->splitMessage($message, 1024);
        $lastIndex = count($chunks) - 1;

        foreach ($chunks as $index => $chunk) {
            if ($index === $lastIndex) {
                $this->whatsapp()->sendReplyButtons($toPhone, $chunk, $buttons);
                continue;
            }

            $this->whatsapp()->sendText($toPhone, $chunk);
        }
    }

    private function sendTextChunks(string $toPhone, string $text): void
    {
        foreach ($this->splitMessage($text, 4096) as $chunk) {
            $this->whatsapp()->sendText($toPhone, $chunk);
        }
    }

    /**
     * @param array<int, mixed> $quickReplies
     * @return array<int, array{label:string,message:string}>
     */
    private function normalizeQuickReplies(array $quickReplies): array
    {
        $normalized = [];

        foreach ($quickReplies as $reply) {
            if (!is_array($reply)) {
                continue;
            }

            $label = trim((string) ($reply['label'] ?? ''));
            $message = trim((string) ($reply['message'] ?? ''));

            if ($label === '' || $message === '') {
                continue;
            }

            $normalized[] = [
                'label' => mb_substr($label, 0, 20),
                'message' => mb_substr($message, 0, 200),
            ];

            if (count($normalized) >= self::MAX_QUICK_REPLIES) {
                break;
            }
        }

        return $normalized;
    }

    /**
     * @param array<int, mixed> $options
     * @return array<int, array{label:string}>
     */
    private function normalizeSelectionOptions(array $options): array
    {
        $normalized = [];

        foreach ($options as $option) {
            if (!is_array($option)) {
                continue;
            }

            $label = trim((string) ($option['nome'] ?? $option['label'] ?? ''));
            if ($label === '') {
                continue;
            }

            $normalized[] = [
                'label' => mb_substr($label, 0, 20),
            ];
        }

        return $normalized;
    }

    private function appendSuggestion(string $text, ?string $suggestion = null): string
    {
        $text = trim($text);
        $suggestion = trim((string) $suggestion);

        if ($suggestion === '') {
            return $text;
        }

        if (str_contains(mb_strtolower($text), mb_strtolower($suggestion))) {
            return $text;
        }

        return $text . "\n\n" . $suggestion;
    }

    /**
     * @param array<int, array{label:string}> $options
     */
    private function buildSelectionMessage(string $text, array $options): string
    {
        $message = trim($text);

        if (empty($options)) {
            return $message;
        }

        if (preg_match('/\b1\.\s+/u', $message) === 1) {
            return $message;
        }

        $optionsText = implode("\n", array_map(
            static fn(array $option, int $index): string => ($index + 1) . '. ' . $option['label'],
            $options,
            array_keys($options),
        ));

        return $message . "\n\n" . $optionsText . "\n\nResponda com o numero ou toque em uma opção.";
    }

    /**
     * @return array<int, string>
     */
    private function splitMessage(string $text, int $limit): array
    {
        $text = trim($text);
        if ($text === '') {
            return [];
        }

        if (mb_strlen($text) <= $limit) {
            return [$text];
        }

        $chunks = [];
        $buffer = '';

        foreach (preg_split("/\n{2,}/u", $text) ?: [$text] as $part) {
            $part = trim((string) $part);
            if ($part === '') {
                continue;
            }

            $candidate = $buffer === '' ? $part : $buffer . "\n\n" . $part;
            if (mb_strlen($candidate) <= $limit) {
                $buffer = $candidate;
                continue;
            }

            if ($buffer !== '') {
                $chunks[] = $buffer;
                $buffer = '';
            }

            while (mb_strlen($part) > $limit) {
                $slice = mb_substr($part, 0, $limit);
                $breakPos = max(
                    (int) mb_strrpos($slice, "\n"),
                    (int) mb_strrpos($slice, '. '),
                    (int) mb_strrpos($slice, ' '),
                );

                if ($breakPos <= 0) {
                    $breakPos = $limit;
                }

                $chunks[] = trim(mb_substr($part, 0, $breakPos));
                $part = trim(mb_substr($part, $breakPos));
            }

            $buffer = $part;
        }

        if ($buffer !== '') {
            $chunks[] = $buffer;
        }

        return array_values(array_filter($chunks, static fn(string $chunk): bool => $chunk !== ''));
    }

    /**
     * @param array<int, array{label:string,message:string}> $quickReplies
     */
    private function cacheQuickReplies(string $phone, array $quickReplies): void
    {
        $this->cache->set(
            $this->quickReplyCacheKey($phone),
            $quickReplies,
            self::QUICK_REPLY_TTL_SECONDS,
        );
    }

    /**
     * @return array<int, array{label:string,message:string}>
     */
    private function getCachedQuickReplies(string $phone): array
    {
        $cached = $this->cache->get($this->quickReplyCacheKey($phone), []);
        return is_array($cached) ? $cached : [];
    }

    private function quickReplyCacheKey(string $phone): string
    {
        return "whatsapp:quick_replies:{$phone}";
    }

    private function handleLocalShortcut(
        WhatsAppMessageDTO $dto,
        Usuario $user,
        WhatsAppMessage $msgRecord,
        string $normalizedBody
    ): bool {
        $command = mb_strtolower(trim($normalizedBody));

        if ($command === '/help') {
            $this->sendHelpMessage($dto->fromPhone);
            $msgRecord->markProcessed('command_help');
            return true;
        }

        if ($command === '/cancel') {
            $this->handleFlowCancellation($dto, $user, $msgRecord);
            return true;
        }

        if ($this->isCancellationText($command) && $this->hasActiveWhatsAppFlow($user->id)) {
            $this->handleFlowCancellation($dto, $user, $msgRecord);
            return true;
        }

        return false;
    }

    private function sendHelpMessage(string $toPhone): void
    {
        $this->sendTextChunks(
            $toPhone,
            "Como usar o Lukrato no WhatsApp:\n\n"
                . "Registrar transações:\n"
                . "- \"almoço 35 hoje\"\n"
                . "- \"recebi freelance 500 ontem\"\n"
                . "- \"netflix 55,90 cartao nubank\"\n\n"
                . "Consultar finanças:\n"
                . "- \"qês?\"\n"
                . "- \"qual meu saldo?\"\n"
                . "- \"maior gasto do mês?\"\n\n"
                . "Planejamento:\n"
                . "- \"criar meta de 5000 para viagem\"\n"
                . "- \"orçamento de 800 para alimentação\"\n\n"
                . "Se faltar algum dado, eu pergunto so o que falta.\n"
                . "Use /cancel para cancelar um fluxo em andamento."
        );
    }

    private function isCancellationText(string $text): bool
    {
        $normalized = mb_strtolower(trim($text));

        if ($normalized === '') {
            return false;
        }

        return preg_match('/\b(cancel|cancela|parar?|desist|sair|deixa\s*pra\s*l[aá]|esquece)\b/iu', $normalized) === 1;
    }

    private function handleFlowCancellation(WhatsAppMessageDTO $dto, Usuario $user, WhatsAppMessage $msgRecord): void
    {
        $cancelledPending = $this->rejectLatestPendingAction($user->id);
        $cancelledConversation = $this->clearActiveConversationState($user->id);

        if (!$cancelledPending && !$cancelledConversation) {
            $this->whatsapp()->sendText($dto->fromPhone, 'Nao havia nenhuma acao ou fluxo ativo para cancelar.');
            $msgRecord->markProcessed('cancel_no_active_flow');
            return;
        }

        $parts = [];
        if ($cancelledPending) {
            $parts[] = 'a confirmacao pendente';
        }
        if ($cancelledConversation) {
            $parts[] = 'o fluxo em andamento';
        }

        $this->whatsapp()->sendText(
            $dto->fromPhone,
            'Tudo certo. Cancelei ' . implode(' e ', $parts) . '.'
        );
        $msgRecord->markProcessed('cancelled_flow');
    }

    private function rejectLatestPendingAction(int $userId): bool
    {
        $pending = PendingAiAction::query()
            ->where('user_id', $userId)
            ->awaiting()
            ->orderByDesc('created_at')
            ->first();

        if ($pending === null) {
            return false;
        }

        return $pending->reject();
    }

    private function clearActiveConversationState(int $userId): bool
    {
        $conversation = $this->getLatestWhatsAppConversation($userId);

        if ($conversation === null || !ConversationStateService::isActive($conversation->id)) {
            return false;
        }

        ConversationStateService::clearState($conversation->id);
        return true;
    }

    private function hasActiveWhatsAppFlow(int $userId): bool
    {
        if (PendingAiAction::where('user_id', $userId)->awaiting()->exists()) {
            return true;
        }

        $conversation = $this->getLatestWhatsAppConversation($userId);
        return $conversation !== null && ConversationStateService::isActive($conversation->id);
    }

    private function getLatestWhatsAppConversation(int $userId): ?AiConversation
    {
        return AiConversation::where('user_id', $userId)
            ->where('titulo', self::CONVERSATION_TITLE)
            ->orderByDesc('updated_at')
            ->first();
    }

    private function allowIncomingSender(string $sender, ?string $messageId = null): bool
    {
        return $this->rateLimiter->allow(
            scope: 'webhook_sender',
            bucket: 'whatsapp',
            identifier: $sender,
            maxAttempts: self::SENDER_LIMIT,
            windowSeconds: self::SENDER_WINDOW_SECONDS,
            context: [
                'channel' => 'whatsapp',
                'sender' => $sender,
                'message_id' => $messageId,
            ],
        );
    }

    private function whatsapp(): WhatsAppService
    {
        return $this->whatsapp;
    }

    private function conversationService(): ChannelConversationService
    {
        return $this->conversationService;
    }
}
