<?php

declare(strict_types=1);

namespace Application\Services\AI\Telegram;

use Application\Config\AiRuntimeConfig;
use Application\Container\ApplicationContainer;
use Application\DTO\AI\AIResponseDTO;
use Application\DTO\AI\TelegramMessageDTO;
use Application\Enums\AI\AIChannel;
use Application\Enums\LogCategory;
use Application\Enums\LogLevel;
use Application\Models\AiConversation;
use Application\Models\PendingAiAction;
use Application\Models\TelegramMessage;
use Application\Models\Usuario;
use Application\Repositories\ContaRepository;
use Application\Services\AI\AIQuotaService;
use Application\Services\AI\Actions\ActionRegistry;
use Application\Services\AI\AiLogService;
use Application\Services\AI\ChannelConversationService;
use Application\Services\AI\ConversationStateService;
use Application\Services\AI\IntentRules\ConfirmationIntentRule;
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

class TelegramWebhookWorkflowService
{
    private const SENDER_LIMIT = 30;
    private const SENDER_WINDOW_SECONDS = 60;
    private const QUICK_REPLY_TTL_SECONDS = 900;
    private const MAX_QUICK_REPLIES = 3;
    private const CONVERSATION_TITLE = 'Telegram';

    private TelegramService $telegram;
    private ChannelConversationService $conversationService;
    private MediaRouterService $mediaRouterService;
    private ContaRepository $contaRepository;
    private CacheService $cache;
    private ActionRegistry $actionRegistry;
    private AIRateLimiter $rateLimiter;
    private ?AiRuntimeConfig $aiRuntimeConfig = null;

    public function __construct(
        ?TelegramService $telegram = null,
        ?ChannelConversationService $conversationService = null,
        ?MediaRouterService $mediaRouterService = null,
        ?ContaRepository $contaRepository = null,
        ?CacheService $cache = null,
        ?ActionRegistry $actionRegistry = null,
        ?AIRateLimiter $rateLimiter = null
    ) {
        $this->telegram = ApplicationContainer::resolveOrNew($telegram, TelegramService::class);
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

        $dto = TelegramMessageDTO::fromTelegramUpdate($payload);

        if ($dto === null || TelegramMessage::alreadyProcessed($dto->updateId)) {
            return;
        }

        $msgRecord = TelegramMessage::create([
            'tg_update_id' => $dto->updateId,
            'tg_message_id' => $dto->messageId,
            'chat_id' => $dto->chatId,
            'direction' => 'incoming',
            'type' => $dto->type,
            'body' => $dto->body,
            'metadata' => $dto->rawPayload,
            'processing_status' => 'received',
            'media_file_id' => $dto->fileId,
            'media_mime_type' => $dto->mimeType,
            'media_file_size' => $dto->fileSize,
            'media_filename' => $dto->filename,
        ]);

        try {
            $this->processUpdate($dto, $msgRecord);
        } catch (\Throwable $e) {
            $msgRecord->markFailed($e->getMessage());

            LogService::persist(
                LogLevel::ERROR,
                LogCategory::WEBHOOK,
                'Erro ao processar update Telegram',
                [
                    'update_id' => $dto->updateId,
                    'chat_id' => $dto->chatId,
                    'error' => $e->getMessage(),
                ],
            );
        }
    }

    private function processUpdate(TelegramMessageDTO $dto, TelegramMessage $msgRecord): void
    {
        if (!$this->allowIncomingSender($dto->chatId, $dto->updateId)) {
            $msgRecord->markIgnored();
            return;
        }

        if ($dto->callbackQueryId) {
            $this->telegram()->answerCallbackQuery($dto->callbackQueryId);
        }

        if ($dto->isCommand()) {
            $this->handleCommand($dto, $msgRecord);
            return;
        }

        $user = TelegramUserResolver::resolve($dto->chatId);

        if ($user === null) {
            if (preg_match('/^\d{6}$/', trim($dto->body))) {
                $this->handleVerificationCode($dto, $msgRecord);
                return;
            }

            $this->telegram()->sendText(
                $dto->chatId,
                "Olá! Você ainda não vinculou seu Telegram ao Lukrato.\n\n"
                    . "Acesse seu painel em lukrato.com.br → Perfil → Telegram para gerar um código de verificação."
            );
            $msgRecord->markIgnored();
            return;
        }

        $msgRecord->update(['user_id' => $user->id]);

        if ($dto->isFlowCancellation() || ($this->isCancellationText($dto->body) && $this->hasActiveTelegramFlow($user->id))) {
            $this->handleFlowCancellation($dto, $user, $msgRecord);
            return;
        }

        if ($dto->isDocument() || $dto->isVideo()) {
            $this->handleMediaMessage($dto, $user, $msgRecord);
            return;
        }

        if ($dto->isVoice()) {
            $this->handleVoiceMessage($dto, $user, $msgRecord);
            return;
        }

        if ($dto->isPhoto()) {
            $this->handlePhotoMessage($dto, $user, $msgRecord);
            return;
        }

        if ($dto->isAccountSelection()) {
            $this->handleAccountSelection($dto, $user, $msgRecord);
            return;
        }

        if ($dto->isOptionSelection()) {
            $this->handleOptionSelection($dto, $user, $msgRecord);
            return;
        }

        if ($dto->isQuickReplySelection()) {
            $this->handleQuickReplySelection($dto, $user, $msgRecord);
            return;
        }

        if ($this->shouldHandleConfirmationReply($dto, $user->id)) {
            $this->handleConfirmationReply($dto, $user, $msgRecord);
            return;
        }

        $this->handleNormalMessage($dto, $user, $msgRecord);
    }

    private function handleCommand(TelegramMessageDTO $dto, TelegramMessage $msgRecord): void
    {
        $command = $dto->getCommand();

        switch ($command) {
            case 'start':
                $arg = $dto->getCommandArg();

                if ($arg !== null && preg_match('/^\d{6}$/', $arg)) {
                    $this->handleVerificationCode($dto, $msgRecord, $arg);
                    return;
                }

                $linkedUser = TelegramUserResolver::resolve($dto->chatId);
                if ($linkedUser !== null) {
                    $this->sendQuickReplyButtons(
                        $dto->chatId,
                        "Seu Telegram já está vinculado à conta <b>{$linkedUser->nome}</b>.\n\n"
                            . "Você pode mandar texto, áudio, imagem, PDF e comprovantes.\n"
                            . "Se quiser interromper algum fluxo, use /cancel.",
                        $this->getStarterQuickReplies()
                    );
                    $msgRecord->markProcessed('command_start_linked');
                    return;
                }

                $name = $dto->displayName ? ", {$dto->displayName}" : '';
                $this->telegram()->sendText(
                    $dto->chatId,
                    "👋 Olá{$name}! Eu sou o bot do <b>Lukrato</b>.\n\n"
                        . "📱 Com este bot você pode:\n"
                        . "• Registrar receitas e despesas\n"
                        . "• Consultar saldos e gastos\n"
                        . "• Analisar suas finanças\n"
                        . "• Criar metas e orçamentos\n\n"
                        . "🔗 Para começar, vincule sua conta:\n"
                        . "1. Acesse <b>lukrato.com.br</b> → Perfil → Telegram\n"
                        . "2. Clique em \"Vincular Telegram\"\n"
                        . "3. Envie o código de 6 dígitos aqui\n\n"
                        . "Pronto! Depois é só mandar mensagens como:\n"
                        . "💬 <i>\"almoço 35\"</i>\n"
                        . "💬 <i>\"quanto gastei este mês?\"</i>\n"
                        . "💬 <i>\"analisa meus gastos\"</i>"
                );
                $msgRecord->markProcessed('command_start');
                return;

            case 'help':
                $this->telegram()->sendText(
                    $dto->chatId,
                    "📖 <b>Como usar o Lukrato Bot</b>\n\n"
                        . "💬 <b>Registrar transações:</b>\n"
                        . "• <i>\"almoço 35\"</i> → despesa de R$ 35\n"
                        . "• <i>\"uber 22.50\"</i> → despesa de R$ 22,50\n"
                        . "• <i>\"recebi salário 5000\"</i> → receita de R$ 5.000\n"
                        . "• <i>\"netflix 55.90 cartão nubank\"</i> → no cartão\n"
                        . "• <i>\"parcelei geladeira 1500 em 12x\"</i> → parcelado\n\n"
                        . "📊 <b>Consultar finanças:</b>\n"
                        . "• <i>\"quanto gastei este mês?\"</i>\n"
                        . "• <i>\"qual meu saldo?\"</i>\n"
                        . "• <i>\"maior gasto do mês?\"</i>\n"
                        . "• <i>\"quantos lançamentos tenho?\"</i>\n\n"
                        . "📈 <b>Análise inteligente:</b>\n"
                        . "• <i>\"analisa meus gastos\"</i>\n"
                        . "• <i>\"como estão minhas finanças?\"</i>\n\n"
                        . "🎯 <b>Criar metas e orçamentos:</b>\n"
                        . "• <i>\"criar meta de 5000 para viagem\"</i>\n"
                        . "• <i>\"orçamento de 800 para alimentação\"</i>\n"
                        . "• <i>\"nova categoria Lazer\"</i>\n\n"
                        . "⚙️ <b>Comandos:</b>\n"
                        . "🔗 /vincular - Vincular conta\n"
                        . "❌ /desvincular - Desvincular conta\n"
                        . "📊 /status - Ver status do vínculo"
                        . "\n/cancel - Cancelar fluxo ou confirmação em andamento"
                );
                $msgRecord->markProcessed('command_help');
                return;

            case 'cancel':
                $user = TelegramUserResolver::resolve($dto->chatId);

                if ($user === null) {
                    $this->telegram()->sendText($dto->chatId, 'Não encontrei nenhum fluxo ativo para cancelar.');
                    $msgRecord->markProcessed('command_cancel_without_user');
                    return;
                }

                $this->handleFlowCancellation($dto, $user, $msgRecord);
                return;

            case 'vincular':
                $user = TelegramUserResolver::resolve($dto->chatId);
                if ($user) {
                    $this->telegram()->sendText($dto->chatId, "✅ Seu Telegram já está vinculado ao Lukrato!");
                } else {
                    $this->telegram()->sendText(
                        $dto->chatId,
                        "🔗 Para vincular, acesse lukrato.com.br → Perfil → Telegram e gere um código.\n"
                            . "Depois, envie o código de 6 dígitos aqui."
                    );
                }
                $msgRecord->markProcessed('command_vincular');
                return;

            case 'desvincular':
                $user = TelegramUserResolver::resolve($dto->chatId);
                if ($user) {
                    $user->telegram_chat_id = null;
                    $user->telegram_verified = false;
                    $user->save();
                    $this->telegram()->sendText($dto->chatId, "❌ Telegram desvinculado do Lukrato.");
                } else {
                    $this->telegram()->sendText($dto->chatId, "Seu Telegram não está vinculado a nenhuma conta.");
                }
                $msgRecord->markProcessed('command_desvincular');
                return;

            case 'status':
                $user = TelegramUserResolver::resolve($dto->chatId);
                if ($user) {
                    $this->telegram()->sendText(
                        $dto->chatId,
                        "✅ Telegram vinculado à conta de <b>{$user->nome}</b>."
                    );
                } else {
                    $this->telegram()->sendText($dto->chatId, "❌ Telegram não vinculado.");
                }
                $msgRecord->markProcessed('command_status');
                return;

            default:
                $this->telegram()->sendText(
                    $dto->chatId,
                    "Comando não reconhecido. Digite /help para ver os comandos disponíveis."
                );
                $msgRecord->markProcessed('command_unknown');
                return;
        }
    }

    private function handleVerificationCode(TelegramMessageDTO $dto, TelegramMessage $msgRecord, ?string $code = null): void
    {
        $code = $code ?? trim($dto->body);

        $user = TelegramUserResolver::verifyByCode($dto->chatId, $code);

        if ($user === null) {
            $this->telegram()->sendText(
                $dto->chatId,
                "❌ Código inválido ou expirado. Gere um novo código em lukrato.com.br → Perfil → Telegram."
            );
            $msgRecord->markProcessed('verification_failed');
            return;
        }

        $msgRecord->update(['user_id' => $user->id]);

        $this->sendQuickReplyButtons(
            $dto->chatId,
            "Telegram vinculado com sucesso.\n\n"
                . "Agora você pode registrar transações, consultar seus números e enviar comprovantes direto por aqui.",
            $this->getStarterQuickReplies()
        );
        $msgRecord->markProcessed('verification_success');
    }

    private function handleConfirmationReply(TelegramMessageDTO $dto, Usuario $user, TelegramMessage $msgRecord): void
    {
        DB::transaction(function () use ($dto, $user, $msgRecord) {
            $pending = PendingAiAction::query()
                ->where('user_id', $user->id)
                ->awaiting()
                ->orderByDesc('created_at')
                ->lockForUpdate()
                ->first();

            if ($pending === null) {
                $this->telegram()->sendText(
                    $dto->chatId,
                    "Não encontrei nenhuma transação pendente de confirmação."
                );
                $msgRecord->markProcessed('confirmation_no_pending');
                return;
            }

            if ($dto->isAffirmative()) {
                $action = $this->actionRegistry->resolve($pending->action_type);

                if ($action === null) {
                    $pending->reject();
                    $this->telegram()->sendText($dto->chatId, "⚠️ Tipo de ação desconhecido.");
                    $msgRecord->markProcessed('confirmation_unknown_action');
                    return;
                }

                $payload = $pending->payload;

                if ($pending->action_type === 'create_lancamento' && empty($payload['conta_id'])) {
                    $resolved = $this->resolveAccount($user, $payload);

                    if ($resolved === null) {
                        $pending->reject();
                        $this->telegram()->sendText(
                            $dto->chatId,
                            "⚠️ Você precisa ter pelo menos uma conta cadastrada no Lukrato para registrar lançamentos."
                        );
                        $msgRecord->markProcessed('confirmation_no_account');
                        return;
                    }

                    if (is_array($resolved) && isset($resolved['needs_selection'])) {
                        $this->sendAccountSelectionButtons($dto->chatId, $resolved['contas']);
                        $msgRecord->markProcessed('awaiting_account_selection');
                        return;
                    }

                    $payload['conta_id'] = $resolved;
                    $pending->update(['payload' => $payload]);
                }

                try {
                    $result = $action->execute($user->id, $payload);

                    if (!$result->success) {
                        $pending->reject();
                        $this->telegram()->sendText($dto->chatId, "⚠️ {$result->message}");
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
                    $this->telegram()->sendText(
                        $dto->chatId,
                        "✅ Lançamento registrado!\n\n"
                            . "📝 {$payload['descricao']}\n"
                            . "💰 {$formatted}{$catStr}"
                    );
                    $msgRecord->markProcessed('transaction_confirmed');
                } catch (\Throwable $e) {
                    $pending->reject();
                    $this->telegram()->sendText($dto->chatId, "⚠️ Erro ao registrar: " . $e->getMessage());
                    $msgRecord->markProcessed('confirmation_error');
                }

                return;
            }

            $pending->reject();
            $this->telegram()->sendText($dto->chatId, "❌ Transação cancelada.");
            $msgRecord->markProcessed('transaction_rejected');
        });
    }

    private function handleAccountSelection(TelegramMessageDTO $dto, Usuario $user, TelegramMessage $msgRecord): void
    {
        $contaId = $dto->getSelectedAccountId();

        if ($contaId === null) {
            $this->telegram()->sendText($dto->chatId, "⚠️ Seleção inválida.");
            $msgRecord->markProcessed('account_selection_invalid');
            return;
        }

        $conta = $this->contaRepository->findByIdAndUser($contaId, $user->id);

        if ($conta === null) {
            $this->telegram()->sendText($dto->chatId, "⚠️ Conta não encontrada.");
            $msgRecord->markProcessed('account_selection_not_found');
            return;
        }

        DB::transaction(function () use ($dto, $user, $conta, $msgRecord) {
            $pending = PendingAiAction::query()
                ->where('user_id', $user->id)
                ->awaiting()
                ->orderByDesc('created_at')
                ->lockForUpdate()
                ->first();

            if ($pending === null) {
                $this->telegram()->sendText($dto->chatId, "⚠️ Nenhuma ação pendente encontrada.");
                $msgRecord->markProcessed('account_selection_no_pending');
                return;
            }

            $payload = $pending->payload;
            $payload['conta_id'] = $conta->id;
            $pending->update(['payload' => $payload]);

            $action = $this->actionRegistry->resolve($pending->action_type);

            if ($action === null) {
                $pending->reject();
                $this->telegram()->sendText($dto->chatId, "⚠️ Tipo de ação desconhecido.");
                $msgRecord->markProcessed('account_selection_unknown_action');
                return;
            }

            try {
                $result = $action->execute($user->id, $payload);

                if (!$result->success) {
                    $pending->reject();
                    $this->telegram()->sendText($dto->chatId, "⚠️ {$result->message}");
                    $msgRecord->markProcessed('account_selection_failed');
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
                $this->telegram()->sendText(
                    $dto->chatId,
                    "✅ Lançamento registrado na conta <b>{$conta->nome}</b>!\n\n"
                        . "📝 {$payload['descricao']}\n"
                        . "💰 {$formatted}{$catStr}"
                );
                $msgRecord->markProcessed('account_selected_confirmed');
            } catch (\Throwable $e) {
                $pending->reject();
                $this->telegram()->sendText($dto->chatId, "⚠️ Erro ao registrar: " . $e->getMessage());
                $msgRecord->markProcessed('account_selection_error');
            }
        });
    }

    private function handleOptionSelection(TelegramMessageDTO $dto, Usuario $user, TelegramMessage $msgRecord): void
    {
        $optionIndex = $dto->getSelectedOptionIndex();
        $conversation = $this->getOrCreateConversation($user->id);
        $state = ConversationStateService::getState($conversation->id);
        $options = $state['data']['options'] ?? [];

        if ($optionIndex === null) {
            $this->telegram()->sendText($dto->chatId, "Opção inválida.");
            $msgRecord->markProcessed('option_selection_invalid');
            return;
        }

        if ($state['state'] !== 'awaiting_selection' || !isset($options[$optionIndex])) {
            $this->telegram()->sendText($dto->chatId, "Essa opção expirou. Tente novamente.");
            $msgRecord->markProcessed('option_selection_failed');
            return;
        }

        $textDto = new TelegramMessageDTO(
            updateId: $dto->updateId,
            messageId: $dto->messageId,
            chatId: $dto->chatId,
            type: 'text',
            body: (string) ($optionIndex + 1),
            displayName: $dto->displayName,
            username: $dto->username,
            rawPayload: $dto->rawPayload,
        );

        $this->handleNormalMessage($textDto, $user, $msgRecord);
    }

    private function handleQuickReplySelection(TelegramMessageDTO $dto, Usuario $user, TelegramMessage $msgRecord): void
    {
        $replyIndex = $dto->getSelectedQuickReplyIndex();
        $quickReplies = $this->getCachedQuickReplies($dto->chatId);
        $message = $replyIndex !== null
            ? trim((string) ($quickReplies[$replyIndex]['message'] ?? ''))
            : '';

        if (
            $replyIndex === null
            || !isset($quickReplies[$replyIndex]['message'])
            || $message === ''
        ) {
            $this->telegram()->sendText(
                $dto->chatId,
                "Esse atalho expirou. Me envie a mensagem manualmente ou escolha outro caminho."
            );
            $msgRecord->markProcessed('quick_reply_expired');
            return;
        }

        $textDto = new TelegramMessageDTO(
            updateId: $dto->updateId,
            messageId: $dto->messageId,
            chatId: $dto->chatId,
            type: str_starts_with($message, '/') ? 'command' : 'text',
            body: $message,
            displayName: $dto->displayName,
            username: $dto->username,
            rawPayload: $dto->rawPayload,
        );

        if ($textDto->isCommand()) {
            $this->handleCommand($textDto, $msgRecord);
            return;
        }

        $this->handleNormalMessage($textDto, $user, $msgRecord);
    }

    private function handleVoiceMessage(TelegramMessageDTO $dto, Usuario $user, TelegramMessage $msgRecord): void
    {
        $this->handleMediaMessage($dto, $user, $msgRecord);
    }

    private function handlePhotoMessage(TelegramMessageDTO $dto, Usuario $user, TelegramMessage $msgRecord): void
    {
        $this->handleMediaMessage($dto, $user, $msgRecord);
    }
    private function handleMediaMessage(TelegramMessageDTO $dto, Usuario $user, TelegramMessage $msgRecord): void
    {
        if ($dto->isVideo()) {
            $this->telegram()->sendText(
                $dto->chatId,
                "Vídeos não são suportados. Envie imagem, PDF ou áudio."
            );
            $msgRecord->markProcessed('video_not_supported');
            return;
        }

        if (!AIQuotaService::hasQuotaRemaining($user, 'chat')) {
            $usage = AIQuotaService::getUsage($user);
            $limit = $usage['chat']['limit'] ?? 5;
            $this->telegram()->sendText(
                $dto->chatId,
                "🤖 Você usou suas {$limit} mensagens de IA gratuitas este mês. "
                    . "Faça upgrade para o Pro: https://lukrato.com.br/billing"
            );
            $msgRecord->markProcessed('quota_exceeded');
            return;
        }

        $statusText = $dto->isVoice()
            ? "Transcrevendo audio..."
            : "Analisando arquivo...";
        $this->telegram()->sendText($dto->chatId, $statusText);

        $downloader = new TelegramFileDownloader();
        $fileData = $downloader->downloadByFileId((string) $dto->fileId);

        if ($fileData === null) {
            $this->telegram()->sendText($dto->chatId, "⚠️ Não consegui baixar o arquivo. Tente novamente.");
            $msgRecord->markFailed('file_download_failed');
            return;
        }

        $asset = new MediaAsset(
            sourceType: $dto->type,
            content: $fileData['content'],
            mimeType: $dto->mimeType,
            filename: $dto->filename ?? $fileData['filename'],
            fileSize: $dto->fileSize,
            caption: $dto->caption ?? $dto->body,
            remoteId: $dto->fileId,
        );

        $result = $this->mediaRouterService->process($asset);
        $this->logMediaProcessing($user->id, 'telegram', $dto->fileId, $dto->caption, $result);

        if ($result->isUnsupported()) {
            $this->telegram()->sendText(
                $dto->chatId,
                $result->error ?? "Tipo de arquivo não suportado. Envie imagem, PDF ou áudio."
            );
            $msgRecord->markProcessed('unsupported_media');
            return;
        }

        if (!$result->success) {
            $this->telegram()->sendText(
                $dto->chatId,
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
                $desc = $receipt->data['descricao'] ?? 'Não identifiquei informações financeiras nesse arquivo.';
                $this->telegram()->sendText(
                    $dto->chatId,
                    "📎 {$desc}\n\nPara registrar uma transação, envie um comprovante, nota fiscal, PDF ou descreva o lançamento por texto."
                );
                $msgRecord->markProcessed('media_not_financial');
                return;
            }

            $transactionData = $receipt->toTransactionData();
            if ($transactionData['valor'] <= 0) {
                $desc = $transactionData['descricao'];
                $this->telegram()->sendText(
                    $dto->chatId,
                    "📎 Vi um comprovante de <b>{$desc}</b>, mas não consegui identificar o valor.\n"
                        . "Pode digitar? Ex: <i>\"{$desc} 35.50\"</i>"
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

        $textDto = new TelegramMessageDTO(
            updateId: $dto->updateId,
            messageId: $dto->messageId,
            chatId: $dto->chatId,
            type: 'text',
            body: $result->text,
            displayName: $dto->displayName,
            username: $dto->username,
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

    private function handleNormalMessage(TelegramMessageDTO $dto, Usuario $user, TelegramMessage $msgRecord): void
    {
        $normalizedBody = TextNormalizer::normalize($dto->body);
        $normalizedBody = NumberNormalizer::normalize($normalizedBody);

        if (!AIQuotaService::hasQuotaRemaining($user, 'chat')) {
            $usage = AIQuotaService::getUsage($user);
            $limit = $usage['chat']['limit'] ?? 5;
            $this->telegram()->sendText(
                $dto->chatId,
                "🤖 Você usou suas {$limit} mensagens de IA gratuitas este mês. "
                    . "Faça upgrade para o Pro e tenha IA ilimitada: https://lukrato.com.br/billing"
            );
            $msgRecord->markProcessed('quota_exceeded');
            return;
        }

        $result = $this->conversationService()->processTextTurn(
            $user->id,
            $normalizedBody,
            AIChannel::TELEGRAM,
            self::CONVERSATION_TITLE,
            ['chat_id' => $dto->chatId],
        );

        $this->sendAiResponse($dto->chatId, $result['response'], $msgRecord);
    }

    private function sendAiResponse(string $chatId, AIResponseDTO $response, TelegramMessage $msgRecord): void
    {
        $intent = $response->intent->value ?? 'chat';
        $pendingId = $response->data['pending_id'] ?? $response->data['pending_action_id'] ?? null;

        if (!empty($pendingId)) {
            $chunks = TelegramResponseFormatter::format($response->message);
            $lastIndex = count($chunks) - 1;

            foreach ($chunks as $i => $chunk) {
                if ($i === $lastIndex) {
                    $sent = $this->telegram()->sendConfirmationButtons($chatId, $chunk);
                } else {
                    $sent = $this->telegram()->sendText($chatId, $chunk);
                }

                if (!$sent) {
                    $this->markTelegramSendFailure($msgRecord, $chatId, 'send_ai_pending', $intent);
                    return;
                }
            }

            $msgRecord->markProcessed($intent . '_pending');
            return;
        }

        if (!empty($response->data['options']) && ($response->data['action'] ?? '') === 'awaiting_selection') {
            if (!$this->sendSelectionButtons($chatId, $response->message, $response->data['options'])) {
                $this->markTelegramSendFailure($msgRecord, $chatId, 'send_ai_selection', $intent);
                return;
            }

            $msgRecord->markProcessed($intent . '_awaiting_selection');
            return;
        }

        if (!empty($response->data['quick_replies']) && is_array($response->data['quick_replies'])) {
            if (!$this->sendQuickReplyButtons(
                $chatId,
                $response->message,
                $response->data['quick_replies'],
                $response->data['suggestion'] ?? null,
            )) {
                $this->markTelegramSendFailure($msgRecord, $chatId, 'send_ai_quick_replies', $intent);
                return;
            }

            $msgRecord->markProcessed($intent . '_quick_replies');
            return;
        }

        foreach (TelegramResponseFormatter::format($response->message) as $chunk) {
            if (!$this->telegram()->sendText($chatId, $chunk)) {
                $this->markTelegramSendFailure($msgRecord, $chatId, 'send_ai_text', $intent);
                return;
            }
        }

        $msgRecord->markProcessed($intent);
    }

    private function markTelegramSendFailure(
        TelegramMessage $msgRecord,
        string $chatId,
        string $operation,
        string $intent
    ): void {
        $msgRecord->markFailed("telegram_send_failed: {$operation}");
        $telegramError = $this->telegram()->lastErrorMessage();
        $errorMessage = "Falha ao enviar resposta para o Telegram ({$operation}).";

        if ($telegramError !== null && $telegramError !== '') {
            $errorMessage .= " Detalhe: {$telegramError}";
        }

        AiLogService::log([
            'user_id' => $msgRecord->user_id,
            'type' => 'chat',
            'channel' => 'telegram',
            'prompt' => mb_substr((string) ($msgRecord->body ?? "Telegram {$operation}"), 0, 5000),
            'response' => null,
            'provider' => 'telegram',
            'model' => 'bot-api',
            'tokens_prompt' => 0,
            'tokens_completion' => 0,
            'tokens_total' => 0,
            'response_time_ms' => 0,
            'success' => false,
            'error_message' => mb_substr($errorMessage, 0, 1000),
            'source' => 'delivery',
            'confidence' => 0,
            'prompt_version' => 'telegram_delivery_v1',
        ]);

        LogService::persist(
            LogLevel::WARNING,
            LogCategory::WEBHOOK,
            'Falha ao enviar resposta para o Telegram',
            [
                'action' => 'telegram_send_response',
                'chat_id' => $chatId,
                'operation' => $operation,
                'intent' => $intent,
                'telegram_message_id' => $msgRecord->id ?? null,
                'telegram_error' => $telegramError,
            ],
        );
    }

    private function shouldHandleConfirmationReply(TelegramMessageDTO $dto, int $userId): bool
    {
        if ($dto->isConfirmationCallback()) {
            return true;
        }

        if (
            !ConfirmationIntentRule::isAffirmative($dto->body)
            && !ConfirmationIntentRule::isNegative($dto->body)
        ) {
            return false;
        }

        return PendingAiAction::where('user_id', $userId)
            ->awaiting()
            ->exists();
    }

    private function isCancellationText(string $text): bool
    {
        $normalized = mb_strtolower(trim($text));

        if ($normalized === '') {
            return false;
        }

        return preg_match('/\b(cancel|cancela|parar?|desist|sair|deixa\s*pra\s*l[á]|esquece)\b/iu', $normalized) === 1;
    }

    private function handleFlowCancellation(TelegramMessageDTO $dto, Usuario $user, TelegramMessage $msgRecord): void
    {
        $cancelledPending = $this->rejectLatestPendingAction($user->id);
        $cancelledConversation = $this->clearActiveConversationState($user->id);

        if (!$cancelledPending && !$cancelledConversation) {
            $this->telegram()->sendText($dto->chatId, "Não havia nenhuma ação ou fluxo ativo para cancelar.");
            $msgRecord->markProcessed('cancel_no_active_flow');
            return;
        }

        $parts = [];
        if ($cancelledPending) {
            $parts[] = 'a confirmação pendente';
        }
        if ($cancelledConversation) {
            $parts[] = 'o fluxo em andamento';
        }

        $this->telegram()->sendText(
            $dto->chatId,
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
        $conversation = $this->getLatestTelegramConversation($userId);

        if ($conversation === null || !ConversationStateService::isActive($conversation->id)) {
            return false;
        }

        ConversationStateService::clearState($conversation->id);
        return true;
    }

    private function hasActiveTelegramFlow(int $userId): bool
    {
        if (PendingAiAction::where('user_id', $userId)->awaiting()->exists()) {
            return true;
        }

        $conversation = $this->getLatestTelegramConversation($userId);
        return $conversation !== null && ConversationStateService::isActive($conversation->id);
    }

    private function getLatestTelegramConversation(int $userId): ?AiConversation
    {
        return AiConversation::where('user_id', $userId)
            ->where('titulo', self::CONVERSATION_TITLE)
            ->orderByDesc('updated_at')
            ->first();
    }

    private function sendQuickReplyButtons(string $chatId, string $text, array $quickReplies, ?string $suggestion = null): bool
    {
        $normalizedReplies = $this->normalizeQuickReplies($quickReplies);
        $message = $this->appendSuggestion($text, $suggestion);

        if (empty($normalizedReplies)) {
            foreach (TelegramResponseFormatter::format($message) as $chunk) {
                if (!$this->telegram()->sendText($chatId, $chunk)) {
                    return false;
                }
            }

            return true;
        }

        $this->cacheQuickReplies($chatId, $normalizedReplies);

        $rows = [];
        foreach ($normalizedReplies as $index => $reply) {
            $rows[] = [[
                'text' => $reply['label'],
                'callback_data' => "quick_reply_{$index}",
            ]];
        }

        $chunks = TelegramResponseFormatter::format($message);
        $lastIndex = count($chunks) - 1;

        foreach ($chunks as $index => $chunk) {
            if ($index === $lastIndex) {
                if (!$this->telegram()->sendInlineKeyboard($chatId, $chunk, $rows)) {
                    $inlineKeyboardError = $this->telegram()->lastErrorMessage();

                    if ($this->sendQuickReplyFallbackText($chatId, $chunk, $normalizedReplies)) {
                        LogService::persist(
                            LogLevel::WARNING,
                            LogCategory::WEBHOOK,
                            'Botões rápidos do Telegram falharam; resposta enviada como texto',
                            [
                                'action' => 'telegram_quick_reply_fallback',
                                'chat_id' => $chatId,
                                'telegram_error' => $inlineKeyboardError,
                            ],
                        );

                        return true;
                    }

                    return false;
                }

                continue;
            }

            if (!$this->telegram()->sendText($chatId, $chunk)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<int, array{label:string,message:string}> $quickReplies
     */
    private function sendQuickReplyFallbackText(string $chatId, string $htmlText, array $quickReplies): bool
    {
        $plainText = $this->telegramHtmlToPlainText($htmlText);
        $labels = array_values(array_filter(array_map(
            static fn(array $reply): string => trim($reply['label']),
            $quickReplies,
        )));

        if ($labels !== []) {
            $plainText .= "\n\nOpcoes: " . implode(' | ', $labels);
        }

        return $this->telegram()->sendPlainText($chatId, $plainText);
    }

    private function telegramHtmlToPlainText(string $htmlText): string
    {
        $plainText = strip_tags($htmlText);
        $plainText = html_entity_decode($plainText, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim($plainText) !== '' ? trim($plainText) : 'Resposta pronta.';
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
                'label' => mb_substr($label, 0, 32),
                'message' => mb_substr($message, 0, 200),
            ];

            if (count($normalized) >= self::MAX_QUICK_REPLIES) {
                break;
            }
        }

        return $normalized;
    }

    private function cacheQuickReplies(string $chatId, array $quickReplies): void
    {
        $this->cache->set(
            $this->quickReplyCacheKey($chatId),
            $quickReplies,
            self::QUICK_REPLY_TTL_SECONDS,
        );
    }

    private function getCachedQuickReplies(string $chatId): array
    {
        $cached = $this->cache->get($this->quickReplyCacheKey($chatId), []);
        return is_array($cached) ? $cached : [];
    }

    private function quickReplyCacheKey(string $chatId): string
    {
        return "telegram:quick_replies:{$chatId}";
    }

    private function getStarterQuickReplies(): array
    {
        return [
            ['label' => 'Registrar gasto', 'message' => 'quero registrar um gasto'],
            ['label' => 'Ver gastos do mês', 'message' => 'quanto gastei este mês?'],
            ['label' => 'Ver ajuda', 'message' => '/help'],
        ];
    }
    private function handleTransactionExtraction(
        TelegramMessageDTO $dto,
        Usuario $user,
        array $extracted,
        TelegramMessage $msgRecord
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
            'origem' => 'telegram',
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
            'channel' => 'telegram',
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
            'prompt_version' => 'telegram_tx_preview_v1',
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
            . "💰 {$formatted}{$catStr}";

        $this->telegram()->sendConfirmationButtons(
            $dto->chatId,
            $text,
            'confirm_yes',
            'confirm_no',
        );

        $msgRecord->markProcessed('transaction_pending');
    }

    private function getOrCreateConversation(int $userId): AiConversation
    {
        $conversation = AiConversation::where('user_id', $userId)
            ->where('titulo', self::CONVERSATION_TITLE)
            ->where('updated_at', '>=', now()->subHours(24))
            ->orderByDesc('updated_at')
            ->first();

        if ($conversation !== null) {
            return $conversation;
        }

        return AiConversation::create([
            'user_id' => $userId,
            'titulo' => self::CONVERSATION_TITLE,
        ]);
    }

    private function resolveAccount(Usuario $user, array $payload): int|array|null
    {
        $contas = $this->contaRepository->findActive($user->id);

        if ($contas->isEmpty()) {
            return null;
        }

        if ($contas->count() === 1) {
            return $contas->first()->id;
        }

        $nomeCartao = $payload['nome_cartao'] ?? null;
        $descricao = mb_strtolower($payload['descricao'] ?? '');

        if ($nomeCartao !== null) {
            $nomeCartaoLower = mb_strtolower($nomeCartao);

            foreach ($contas as $conta) {
                if (str_contains(mb_strtolower($conta->nome), $nomeCartaoLower)) {
                    return $conta->id;
                }
                if ($conta->instituicao && str_contains(mb_strtolower($conta->instituicao), $nomeCartaoLower)) {
                    return $conta->id;
                }
                if (
                    $conta->instituicaoFinanceira
                    && str_contains(mb_strtolower($conta->instituicaoFinanceira->nome ?? ''), $nomeCartaoLower)
                ) {
                    return $conta->id;
                }
            }
        }

        if ($descricao !== '') {
            foreach ($contas as $conta) {
                $nomeLower = mb_strtolower($conta->nome);
                if (str_contains($descricao, $nomeLower) || str_contains($nomeLower, $descricao)) {
                    return $conta->id;
                }
            }
        }

        return [
            'needs_selection' => true,
            'contas' => $contas,
        ];
    }

    private function sendAccountSelectionButtons(string $chatId, $contas): void
    {
        $rows = [];
        $row = [];

        foreach ($contas as $conta) {
            $label = $conta->nome;
            if ($conta->instituicaoFinanceira) {
                $label .= " ({$conta->instituicaoFinanceira->nome})";
            }

            $row[] = [
                'text' => "🏦 {$label}",
                'callback_data' => "select_conta_{$conta->id}",
            ];

            if (count($row) >= 2) {
                $rows[] = $row;
                $row = [];
            }
        }

        if (!empty($row)) {
            $rows[] = $row;
        }

        $rows[] = [['text' => '❌ Cancelar', 'callback_data' => 'confirm_no']];

        $this->telegram()->sendInlineKeyboard(
            $chatId,
            "🏦 Em qual conta deseja registrar?",
            $rows,
        );
    }

    private function sendSelectionButtons(string $chatId, string $text, array $options): bool
    {
        $rows = [];

        foreach ($options as $i => $option) {
            $label = $option['nome'] ?? $option['name'] ?? $option['titulo'] ?? 'Opção ' . ($i + 1);
            $rows[] = [[
                'text' => $label,
                'callback_data' => "select_option_{$i}",
            ]];
        }

        $rows[] = [['text' => '❌ Cancelar', 'callback_data' => 'cancel_flow']];

        $chunks = TelegramResponseFormatter::format($text);
        $lastIndex = count($chunks) - 1;

        foreach ($chunks as $j => $chunk) {
            if ($j === $lastIndex) {
                if (!$this->telegram()->sendInlineKeyboard($chatId, $chunk, $rows)) {
                    return false;
                }
            } else {
                if (!$this->telegram()->sendText($chatId, $chunk)) {
                    return false;
                }
            }
        }

        return true;
    }

    private function allowIncomingSender(string $sender, ?string $messageId = null): bool
    {
        return $this->rateLimiter->allow(
            scope: 'webhook_sender',
            bucket: 'telegram',
            identifier: $sender,
            maxAttempts: self::SENDER_LIMIT,
            windowSeconds: self::SENDER_WINDOW_SECONDS,
            context: [
                'channel' => 'telegram',
                'sender' => $sender,
                'message_id' => $messageId,
            ],
        );
    }

    private function telegram(): TelegramService
    {
        return $this->telegram;
    }

    private function conversationService(): ChannelConversationService
    {
        return $this->conversationService;
    }
}
