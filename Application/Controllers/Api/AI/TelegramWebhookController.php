<?php

declare(strict_types=1);

namespace Application\Controllers\Api\AI;

use Application\Controllers\BaseController;
use Application\DTO\AI\AIRequestDTO;
use Application\DTO\AI\TelegramMessageDTO;
use Application\Enums\AI\AIChannel;
use Application\Models\AiConversation;
use Application\Models\PendingAiAction;
use Application\Models\TelegramMessage;
use Application\Repositories\ContaRepository;
use Application\Services\AI\AIService;
use Application\Services\AI\AIQuotaService;
use Application\Services\AI\Actions\ActionRegistry;
use Application\Services\AI\ConversationStateService;
use Application\Services\AI\Context\UserContextBuilder;
use Application\Services\AI\ContextCompressor;
use Application\Services\AI\Media\MediaAsset;
use Application\Services\AI\Media\MediaProcessingResult;
use Application\Services\AI\Media\MediaRouterService;
use Application\Services\AI\Media\ReceiptAnalysisResult;
use Application\Services\AI\NLP\NumberNormalizer;
use Application\Services\AI\NLP\TextNormalizer;
use Application\Services\AI\Rules\CategoryRuleEngine;
use Application\Services\AI\Security\AIRateLimiter;
use Application\Models\AiChatMessage;
use Application\Services\AI\TransactionDetectorService;
use Application\Services\AI\Telegram\TelegramResponseFormatter;
use Application\Services\AI\Telegram\TelegramService;
use Application\Services\AI\Telegram\TelegramUserResolver;
use Application\Services\AI\Telegram\TelegramFileDownloader;
use Application\Services\AI\AiLogService;
use Application\Services\Infrastructure\LogService;
use Application\Services\AI\Media\ImageAnalysisService;
use Application\Services\AI\Media\AudioTranscriptionService;
use Application\Enums\LogLevel;
use Application\Enums\LogCategory;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Controller para o webhook do Telegram Bot API.
 *
 * Endpoint:
 *  POST /api/webhook/telegram  → Recepção de updates
 *
 * Padrão: updates válidos retornam 200; secret inválido recebe 403.
 * Idempotência: via telegram_messages.tg_update_id (UNIQUE).
 * Segurança: valida header X-Telegram-Bot-Api-Secret-Token.
 * Canal: Telegram é tratado como "mais um canal" → usa AIService.dispatch() normalmente.
 */
class TelegramWebhookController extends BaseController
{
    private const SENDER_LIMIT = 30;
    private const SENDER_WINDOW_SECONDS = 60;

    private TelegramService $telegram;

    public function __construct()
    {
        parent::__construct();
        $this->telegram = new TelegramService();
    }

    // ─── Webhook Reception (POST) ─────────────────────────────

    /**
     * Recebe updates do Telegram.
     * Updates válidos retornam 200; secret inválido recebe 403.
     */
    public function receive(): void
    {
        // Validar secret token
        $secretHeader = $_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? '';
        $expectedSecret = TelegramService::getWebhookSecret();

        if ($expectedSecret !== '' && $secretHeader !== $expectedSecret) {
            LogService::persist(
                LogLevel::WARNING,
                LogCategory::WEBHOOK,
                'Telegram webhook secret inválido',
                ['ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'],
            );
            http_response_code(403);
            echo 'Forbidden';
            return;
        }

        $rawBody = file_get_contents('php://input');
        $payload = json_decode($rawBody, true);

        // Sempre 200 para o Telegram
        http_response_code(200);
        header('Content-Type: application/json');

        if (!is_array($payload)) {
            echo json_encode(['ok' => true]);
            return;
        }

        // Parsear update via DTO
        $dto = TelegramMessageDTO::fromTelegramUpdate($payload);

        if ($dto === null) {
            echo json_encode(['ok' => true]);
            return;
        }

        // Idempotência: já processamos este update?
        if (TelegramMessage::alreadyProcessed($dto->updateId)) {
            echo json_encode(['ok' => true]);
            return;
        }

        // Registrar mensagem recebida
        $msgRecord = TelegramMessage::create([
            'tg_update_id'      => $dto->updateId,
            'tg_message_id'     => $dto->messageId,
            'chat_id'           => $dto->chatId,
            'direction'         => 'incoming',
            'type'              => $dto->type,
            'body'              => $dto->body,
            'metadata'          => $dto->rawPayload,
            'processing_status' => 'received',
            'media_file_id'     => $dto->fileId,
            'media_mime_type'   => $dto->mimeType,
            'media_file_size'   => $dto->fileSize,
            'media_filename'    => $dto->filename,
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
                    'chat_id'   => $dto->chatId,
                    'error'     => $e->getMessage(),
                ],
            );
        }

        echo json_encode(['ok' => true]);
    }

    // ─── Processing Pipeline ──────────────────────────────────

    /**
     * Pipeline principal de processamento.
     */
    private function processUpdate(TelegramMessageDTO $dto, TelegramMessage $msgRecord): void
    {
        if (!$this->allowIncomingSender($dto->chatId, $dto->updateId)) {
            $msgRecord->markIgnored();
            return;
        }

        // Responder callback_query para remover loading do botão
        if ($dto->callbackQueryId) {
            $this->telegram->answerCallbackQuery($dto->callbackQueryId);
        }

        // Comando /start → tratamento especial
        if ($dto->isCommand()) {
            $this->handleCommand($dto, $msgRecord);
            return;
        }

        // Resolver usuário pelo chat_id
        $user = TelegramUserResolver::resolve($dto->chatId);

        if ($user === null) {
            // Tentar vincular se for um código de 6 dígitos
            if (preg_match('/^\d{6}$/', trim($dto->body))) {
                $this->handleVerificationCode($dto, $msgRecord);
                return;
            }

            $this->telegram->sendText(
                $dto->chatId,
                "Olá! Você ainda não vinculou seu Telegram ao Lukrato.\n\n"
                    . "Acesse seu painel em lukrato.com.br → Perfil → Telegram para gerar um código de verificação."
            );
            $msgRecord->markIgnored();
            return;
        }

        $msgRecord->update(['user_id' => $user->id]);

        if ($dto->isDocument() || $dto->isVideo()) {
            $this->handleMediaMessage($dto, $user, $msgRecord);
            return;
        }

        // Voice/Audio → transcrever e processar como texto
        if ($dto->isVoice()) {
            $this->handleVoiceMessage($dto, $user, $msgRecord);
            return;
        }

        // Photo → analisar comprovante/recibo
        if ($dto->isPhoto()) {
            $this->handlePhotoMessage($dto, $user, $msgRecord);
            return;
        }

        // Callback: seleção de conta (select_conta_X)
        if ($dto->isAccountSelection()) {
            $this->handleAccountSelection($dto, $user, $msgRecord);
            return;
        }

        // Callback: seleção de opção genérica (select_option_X)
        if ($dto->isOptionSelection()) {
            $this->handleOptionSelection($dto, $user, $msgRecord);
            return;
        }

        // Callback: confirmação sim/não
        if ($dto->isConfirmationReply()) {
            $this->handleConfirmationReply($dto, $user, $msgRecord);
            return;
        }

        // Processar como mensagem normal via AIService (pipeline completa)
        $this->handleNormalMessage($dto, $user, $msgRecord);
    }

    /**
     * Trata comandos do bot (/start, /help, etc).
     */
    private function handleCommand(TelegramMessageDTO $dto, TelegramMessage $msgRecord): void
    {
        $command = $dto->getCommand();

        switch ($command) {
            case 'start':
                $arg = $dto->getCommandArg();

                // /start <code> → deep link de verificação
                if ($arg !== null && preg_match('/^\d{6}$/', $arg)) {
                    $this->handleVerificationCode($dto, $msgRecord, $arg);
                    return;
                }

                $name = $dto->displayName ? ", {$dto->displayName}" : '';
                $this->telegram->sendText(
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
                        . "💬 <i>\"almoco 35\"</i>\n"
                        . "💬 <i>\"quanto gastei este mês?\"</i>\n"
                        . "💬 <i>\"analisa meus gastos\"</i>"
                );
                $msgRecord->markProcessed('command_start');
                break;

            case 'help':
                $this->telegram->sendText(
                    $dto->chatId,
                    "📖 <b>Como usar o Lukrato Bot</b>\n\n"
                        . "💬 <b>Registrar transações:</b>\n"
                        . "• <i>\"almoco 35\"</i> → despesa de R$ 35\n"
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
                );
                $msgRecord->markProcessed('command_help');
                break;

            case 'vincular':
                $user = TelegramUserResolver::resolve($dto->chatId);
                if ($user) {
                    $this->telegram->sendText($dto->chatId, "✅ Seu Telegram já está vinculado ao Lukrato!");
                } else {
                    $this->telegram->sendText(
                        $dto->chatId,
                        "🔗 Para vincular, acesse lukrato.com.br → Perfil → Telegram e gere um código.\n"
                            . "Depois, envie o código de 6 dígitos aqui."
                    );
                }
                $msgRecord->markProcessed('command_vincular');
                break;

            case 'desvincular':
                $user = TelegramUserResolver::resolve($dto->chatId);
                if ($user) {
                    $user->telegram_chat_id  = null;
                    $user->telegram_verified = false;
                    $user->save();
                    $this->telegram->sendText($dto->chatId, "❌ Telegram desvinculado do Lukrato.");
                } else {
                    $this->telegram->sendText($dto->chatId, "Seu Telegram não está vinculado a nenhuma conta.");
                }
                $msgRecord->markProcessed('command_desvincular');
                break;

            case 'status':
                $user = TelegramUserResolver::resolve($dto->chatId);
                if ($user) {
                    $this->telegram->sendText(
                        $dto->chatId,
                        "✅ Telegram vinculado à conta de <b>{$user->nome}</b>."
                    );
                } else {
                    $this->telegram->sendText($dto->chatId, "❌ Telegram não vinculado.");
                }
                $msgRecord->markProcessed('command_status');
                break;

            default:
                $this->telegram->sendText(
                    $dto->chatId,
                    "Comando não reconhecido. Digite /help para ver os comandos disponíveis."
                );
                $msgRecord->markProcessed('command_unknown');
                break;
        }
    }

    /**
     * Tenta vincular via código de verificação enviado no chat.
     */
    private function handleVerificationCode(
        TelegramMessageDTO $dto,
        TelegramMessage $msgRecord,
        ?string $code = null,
    ): void {
        $code = $code ?? trim($dto->body);

        $user = TelegramUserResolver::verifyByCode($dto->chatId, $code);

        if ($user === null) {
            $this->telegram->sendText(
                $dto->chatId,
                "❌ Código inválido ou expirado. Gere um novo código em lukrato.com.br → Perfil → Telegram."
            );
            $msgRecord->markProcessed('verification_failed');
            return;
        }

        $msgRecord->update(['user_id' => $user->id]);

        $this->telegram->sendText(
            $dto->chatId,
            "✅ Telegram vinculado com sucesso!\n\n"
                . "Agora você pode registrar transações enviando mensagens como:\n"
                . "💬 <i>\"almoco 35\"</i>\n"
                . "💬 <i>\"recebi salário 5000\"</i>"
        );
        $msgRecord->markProcessed('verification_success');
    }

    /**
     * Trata resposta Sim/Não a uma transação pendente.
     * Unificado: usa PendingAiAction + ActionRegistry (mesmo pipeline do web chat).
     */
    private function handleConfirmationReply(
        TelegramMessageDTO $dto,
        \Application\Models\Usuario $user,
        TelegramMessage $msgRecord,
    ): void {
        DB::transaction(function () use ($dto, $user, $msgRecord) {
            $pending = PendingAiAction::query()
                ->where('user_id', $user->id)
                ->awaiting()
                ->orderByDesc('created_at')
                ->lockForUpdate()
                ->first();

            if ($pending === null) {
                $this->telegram->sendText(
                    $dto->chatId,
                    "Não encontrei nenhuma transação pendente de confirmação."
                );
                $msgRecord->markProcessed('confirmation_no_pending');
                return;
            }

            if ($dto->isAffirmative()) {
                // Executar via ActionRegistry (mesmo caminho do web chat)
                $actionRegistry = new ActionRegistry();
                $action = $actionRegistry->resolve($pending->action_type);

                if ($action === null) {
                    $pending->reject();
                    $this->telegram->sendText($dto->chatId, "⚠️ Tipo de ação desconhecido.");
                    $msgRecord->markProcessed('confirmation_unknown_action');
                    return;
                }

                $payload = $pending->payload;

                // Seleção inteligente de conta para lançamentos
                if ($pending->action_type === 'create_lancamento' && empty($payload['conta_id'])) {
                    $resolved = $this->resolveAccount($user, $payload);

                    if ($resolved === null) {
                        $pending->reject();
                        $this->telegram->sendText(
                            $dto->chatId,
                            "⚠️ Você precisa ter pelo menos uma conta cadastrada no Lukrato para registrar lançamentos."
                        );
                        $msgRecord->markProcessed('confirmation_no_account');
                        return;
                    }

                    // Se retornou array de contas → precisa escolher
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
                        $this->telegram->sendText($dto->chatId, "⚠️ {$result->message}");
                        $msgRecord->markProcessed('confirmation_failed');
                        return;
                    }

                    $pending->confirm();

                    // Aprender categorização
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
                    $this->telegram->sendText(
                        $dto->chatId,
                        "✅ Lançamento registrado!\n\n"
                            . "📝 {$payload['descricao']}\n"
                            . "💰 {$formatted}{$catStr}"
                    );
                    $msgRecord->markProcessed('transaction_confirmed');
                } catch (\Throwable $e) {
                    $pending->reject();
                    $this->telegram->sendText($dto->chatId, "⚠️ Erro ao registrar: " . $e->getMessage());
                    $msgRecord->markProcessed('confirmation_error');
                }
            } else {
                $pending->reject();
                $this->telegram->sendText($dto->chatId, "❌ Transação cancelada.");
                $msgRecord->markProcessed('transaction_rejected');
            }
        });
    }

    /**
     * Trata seleção de conta via botão inline (callback: select_conta_X).
     */
    private function handleAccountSelection(
        TelegramMessageDTO $dto,
        \Application\Models\Usuario $user,
        TelegramMessage $msgRecord,
    ): void {
        $contaId = $dto->getSelectedAccountId();

        if ($contaId === null) {
            $this->telegram->sendText($dto->chatId, "⚠️ Seleção inválida.");
            $msgRecord->markProcessed('account_selection_invalid');
            return;
        }

        // Verificar que a conta pertence ao usuário
        $contaRepo = new ContaRepository();
        $conta = $contaRepo->findByIdAndUser($contaId, $user->id);

        if ($conta === null) {
            $this->telegram->sendText($dto->chatId, "⚠️ Conta não encontrada.");
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
                $this->telegram->sendText($dto->chatId, "⚠️ Nenhuma ação pendente encontrada.");
                $msgRecord->markProcessed('account_selection_no_pending');
                return;
            }

            $payload = $pending->payload;
            $payload['conta_id'] = $conta->id;
            $pending->update(['payload' => $payload]);

            // Executar a ação com a conta selecionada
            $actionRegistry = new ActionRegistry();
            $action = $actionRegistry->resolve($pending->action_type);

            if ($action === null) {
                $pending->reject();
                $this->telegram->sendText($dto->chatId, "⚠️ Tipo de ação desconhecido.");
                $msgRecord->markProcessed('account_selection_unknown_action');
                return;
            }

            try {
                $result = $action->execute($user->id, $payload);

                if (!$result->success) {
                    $pending->reject();
                    $this->telegram->sendText($dto->chatId, "⚠️ {$result->message}");
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
                $this->telegram->sendText(
                    $dto->chatId,
                    "✅ Lançamento registrado na conta <b>{$conta->nome}</b>!\n\n"
                        . "📝 {$payload['descricao']}\n"
                        . "💰 {$formatted}{$catStr}"
                );
                $msgRecord->markProcessed('account_selected_confirmed');
            } catch (\Throwable $e) {
                $pending->reject();
                $this->telegram->sendText($dto->chatId, "⚠️ Erro ao registrar: " . $e->getMessage());
                $msgRecord->markProcessed('account_selection_error');
            }
        });
    }

    /**
     * Trata seleção de opção genérica via botão inline (multi-turn: select_option_X).
     * Usado para seleção de cartão, categoria, etc. no fluxo multi-turn.
     */
    private function handleOptionSelection(
        TelegramMessageDTO $dto,
        \Application\Models\Usuario $user,
        TelegramMessage $msgRecord,
    ): void {
        $optionIndex = $dto->getSelectedOptionIndex();

        if ($optionIndex === null) {
            $this->telegram->sendText($dto->chatId, "⚠️ Seleção inválida.");
            $msgRecord->markProcessed('option_selection_invalid');
            return;
        }

        // Buscar conversa ativa do Telegram para este usuário
        $conversation = $this->getOrCreateConversation($user->id);

        // Usar ConversationStateService para resolver a seleção
        // O índice do botão (1-based) é passado como mensagem textual
        $resolved = ConversationStateService::resolveSelection(
            $conversation->id,
            (string) ($optionIndex + 1)  // 0-based → 1-based
        );

        if ($resolved === null) {
            $this->telegram->sendText($dto->chatId, "⚠️ Opção inválida ou expirada. Tente novamente.");
            $msgRecord->markProcessed('option_selection_failed');
            return;
        }

        // Continuar o fluxo normal com os dados resolvidos (re-dispatch via AI)
        $this->handleNormalMessage($dto, $user, $msgRecord);
    }

    /**
     * Trata mensagem de voz/áudio: download → transcrição Whisper → pipeline de texto.
     */
    private function handleVoiceMessage(
        TelegramMessageDTO $dto,
        \Application\Models\Usuario $user,
        TelegramMessage $msgRecord,
    ): void {
        $this->handleMediaMessage($dto, $user, $msgRecord);
        return;

        // Verificar quota antes de consumir API
        if (!AIQuotaService::hasQuotaRemaining($user, 'chat')) {
            $usage = AIQuotaService::getUsage($user);
            $limit = $usage['chat']['limit'] ?? 5;
            $this->telegram->sendText(
                $dto->chatId,
                "🤖 Você usou suas {$limit} mensagens de IA gratuitas este mês. "
                    . "Faça upgrade para o Pro: https://lukrato.com.br/billing"
            );
            $msgRecord->markProcessed('quota_exceeded');
            return;
        }

        // Indicar que está processando
        $this->telegram->sendText($dto->chatId, "🎙️ Transcrevendo áudio...");

        // Baixar arquivo do Telegram
        $downloader = new TelegramFileDownloader();
        $fileData = $downloader->downloadByFileId($dto->fileId);

        if ($fileData === null) {
            $this->telegram->sendText($dto->chatId, "⚠️ Não consegui baixar o áudio. Tente novamente.");
            $msgRecord->markFailed('file_download_failed');
            return;
        }

        // Verificar formato suportado
        $transcriber = new AudioTranscriptionService();
        $ext = $fileData['extension'] ?? 'ogg';

        if (!$transcriber->isFormatSupported($ext)) {
            $this->telegram->sendText($dto->chatId, "⚠️ Formato de áudio não suportado. Envie um áudio de voz normal.");
            $msgRecord->markProcessed('unsupported_audio_format');
            return;
        }

        // Transcrever via Whisper
        $result = $transcriber->transcribe($fileData['content'], "audio.{$ext}");

        if (!$result->success || $result->text === '') {
            $this->telegram->sendText(
                $dto->chatId,
                "⚠️ Não consegui entender o áudio. Pode tentar novamente ou digitar a mensagem?"
            );
            $msgRecord->markFailed('transcription_failed: ' . ($result->error ?? 'empty'));
            return;
        }

        // Logar transcrição
        AiLogService::log([
            'user_id'           => $user->id,
            'type'              => 'audio_transcription',
            'channel'           => 'telegram',
            'prompt'            => "[voice:{$dto->fileId}]",
            'response'          => $result->text,
            'provider'          => 'openai',
            'model'             => 'whisper-1',
            'tokens_prompt'     => 0,
            'tokens_completion' => 0,
            'tokens_total'      => 0,
            'response_time_ms'  => $result->durationMs,
            'success'           => true,
        ]);

        // Atualizar registro com transcrição
        $msgRecord->update([
            'transcription' => $result->text,
            'body'          => $result->text,
        ]);

        // Criar DTO sintético com texto transcrito e processar como mensagem normal
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

    /**
     * Trata mensagem de foto: download → análise Vision → criar transação ou pipeline de texto.
     */
    private function handlePhotoMessage(
        TelegramMessageDTO $dto,
        \Application\Models\Usuario $user,
        TelegramMessage $msgRecord,
    ): void {
        $this->handleMediaMessage($dto, $user, $msgRecord);
        return;

        // Verificar quota
        if (!AIQuotaService::hasQuotaRemaining($user, 'chat')) {
            $usage = AIQuotaService::getUsage($user);
            $limit = $usage['chat']['limit'] ?? 5;
            $this->telegram->sendText(
                $dto->chatId,
                "🤖 Você usou suas {$limit} mensagens de IA gratuitas este mês. "
                    . "Faça upgrade para o Pro: https://lukrato.com.br/billing"
            );
            $msgRecord->markProcessed('quota_exceeded');
            return;
        }

        // Indicar que está processando
        $this->telegram->sendText($dto->chatId, "📷 Analisando imagem...");

        // Baixar arquivo do Telegram
        $downloader = new TelegramFileDownloader();
        $fileData = $downloader->downloadByFileId($dto->fileId);

        if ($fileData === null) {
            $this->telegram->sendText($dto->chatId, "⚠️ Não consegui baixar a imagem. Tente novamente.");
            $msgRecord->markFailed('file_download_failed');
            return;
        }

        // Analisar via GPT-4o-mini Vision
        $analyzer = new ImageAnalysisService();
        $mimeType = $dto->mimeType ?? 'image/jpeg';
        $analysisResult = $analyzer->analyzeReceipt($fileData['content'], $mimeType);

        // Logar análise
        AiLogService::log([
            'user_id'           => $user->id,
            'type'              => 'image_analysis',
            'channel'           => 'telegram',
            'prompt'            => "[photo:{$dto->fileId}]" . ($dto->caption ? " caption: {$dto->caption}" : ''),
            'response'          => $analysisResult->rawText,
            'provider'          => 'openai',
            'model'             => $_ENV['OPENAI_MODEL'] ?? 'gpt-4o-mini',
            'tokens_prompt'     => 0,
            'tokens_completion' => 0,
            'tokens_total'      => $analysisResult->tokensUsed,
            'response_time_ms'  => 0,
            'success'           => $analysisResult->success,
        ]);

        if (!$analysisResult->success) {
            $this->telegram->sendText(
                $dto->chatId,
                "⚠️ Não consegui analisar a imagem. Pode descrever a transação por texto?"
            );
            $msgRecord->markFailed('image_analysis_failed: ' . ($analysisResult->error ?? 'unknown'));
            return;
        }

        // Se não é comprovante financeiro
        if (!$analysisResult->isFinancial()) {
            $desc = $analysisResult->data['descricao'] ?? 'Não identifiquei informações financeiras nesta imagem.';
            $this->telegram->sendText(
                $dto->chatId,
                "📷 {$desc}\n\nPara registrar uma transação, envie uma foto de um comprovante, recibo ou nota fiscal."
            );
            $msgRecord->update(['transcription' => $desc]);
            $msgRecord->markProcessed('image_not_financial');
            return;
        }

        // Dados financeiros extraídos
        $extracted = $analysisResult->data;
        $msgRecord->update(['transcription' => json_encode($extracted, JSON_UNESCAPED_UNICODE)]);

        // Montar dados da transação no formato esperado por handleTransactionExtraction
        $transactionData = [
            'descricao' => $extracted['descricao']
                ?? ($extracted['estabelecimento'] ?? 'Compra'),
            'valor'     => (float) ($extracted['valor'] ?? 0),
            'tipo'      => ($extracted['tipo'] ?? 'despesa') === 'receita' ? 'receita' : 'despesa',
            'data'      => $extracted['data'] ?? date('Y-m-d'),
        ];

        if (!empty($extracted['forma_pagamento']) && $extracted['forma_pagamento'] !== 'null') {
            $transactionData['forma_pagamento'] = $extracted['forma_pagamento'];
        }

        // Se não conseguiu extrair valor
        if ($transactionData['valor'] <= 0) {
            $desc = $transactionData['descricao'];
            $this->telegram->sendText(
                $dto->chatId,
                "📷 Vi um comprovante de <b>{$desc}</b>, mas não consegui identificar o valor.\n"
                    . "Pode digitar? Ex: <i>\"{$desc} 35.50\"</i>"
            );
            $msgRecord->markProcessed('image_no_amount');
            return;
        }

        // Reutilizar fluxo existente de criação de transação
        $this->handleTransactionExtraction($dto, $user, $transactionData, $msgRecord);
    }

    private function handleMediaMessage(
        TelegramMessageDTO $dto,
        \Application\Models\Usuario $user,
        TelegramMessage $msgRecord,
    ): void {
        if (!AIQuotaService::hasQuotaRemaining($user, 'chat')) {
            $usage = AIQuotaService::getUsage($user);
            $limit = $usage['chat']['limit'] ?? 5;
            $this->telegram->sendText(
                $dto->chatId,
                "🤖 Você usou suas {$limit} mensagens de IA gratuitas este mês. "
                    . "Faça upgrade para o Pro: https://lukrato.com.br/billing"
            );
            $msgRecord->markProcessed('quota_exceeded');
            return;
        }

        $statusText = $dto->isVoice()
            ? "🎙️ Transcrevendo áudio..."
            : ($dto->isVideo() ? "🎬 Processando vídeo..." : "📎 Analisando arquivo...");
        $this->telegram->sendText($dto->chatId, $statusText);

        $downloader = new TelegramFileDownloader();
        $fileData = $downloader->downloadByFileId((string) $dto->fileId);

        if ($fileData === null) {
            $this->telegram->sendText($dto->chatId, "⚠️ Não consegui baixar o arquivo. Tente novamente.");
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

        $result = (new MediaRouterService())->process($asset);
        $this->logMediaProcessing($user->id, 'telegram', $dto->fileId, $dto->caption, $result);

        if ($result->isUnsupported()) {
            $this->telegram->sendText(
                $dto->chatId,
                "⚠️ Ainda não consigo processar esse tipo de arquivo. Envie imagem, PDF, áudio ou vídeo curto."
            );
            $msgRecord->markProcessed('unsupported_media');
            return;
        }

        if (!$result->success) {
            $this->telegram->sendText(
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
                $desc = $receipt->data['descricao'] ?? 'Nao identifiquei informacoes financeiras nesse arquivo.';
                $this->telegram->sendText(
                    $dto->chatId,
                    "📎 {$desc}\n\nPara registrar uma transação, envie um comprovante, nota fiscal, PDF ou descreva o lançamento por texto."
                );
                $msgRecord->markProcessed('media_not_financial');
                return;
            }

            $transactionData = $receipt->toTransactionData();
            if ($transactionData['valor'] <= 0) {
                $desc = $transactionData['descricao'];
                $this->telegram->sendText(
                    $dto->chatId,
                    "📎 Vi um comprovante de <b>{$desc}</b>, mas nao consegui identificar o valor.\n"
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
        MediaProcessingResult $result,
    ): void {
        $logType = in_array($result->operation, ['receipt_analysis', 'document_analysis'], true)
            ? 'image_analysis'
            : 'audio_transcription';

        AiLogService::log([
            'user_id'           => $userId,
            'type'              => $logType,
            'channel'           => $channel,
            'prompt'            => "[media:{$fileId}]" . ($caption ? " caption: {$caption}" : ''),
            'response'          => $result->text !== '' ? $result->text : json_encode($result->data, JSON_UNESCAPED_UNICODE),
            'provider'          => 'openai',
            'model'             => $_ENV['OPENAI_VISION_MODEL']
                ?? $_ENV['OPENAI_TRANSCRIPTION_MODEL']
                ?? $_ENV['OPENAI_MODEL']
                ?? 'gpt-4o-mini',
            'tokens_prompt'     => 0,
            'tokens_completion' => 0,
            'tokens_total'      => $result->tokensUsed,
            'response_time_ms'  => $result->durationMs,
            'success'           => $result->success,
            'error_message'     => $result->error,
        ]);
    }

    /**
     * Trata mensagem normal: pipeline completa de IA com IntentRouter.
     * Detecta: Quick Queries, Análise, Transações, Metas, Orçamentos, Chat...
     */
    private function handleNormalMessage(
        TelegramMessageDTO $dto,
        \Application\Models\Usuario $user,
        TelegramMessage $msgRecord,
    ): void {
        $normalizedBody = TextNormalizer::normalize($dto->body);
        $normalizedBody = NumberNormalizer::normalize($normalizedBody);
        // Tentar extração de transação primeiro (regex, 0 tokens)
        $extracted = TransactionDetectorService::extract($normalizedBody);

        if ($extracted !== null) {
            $this->handleTransactionExtraction($dto, $user, $extracted, $msgRecord);
            return;
        }

        // Verificar quota de chat antes de consumir IA
        if (!AIQuotaService::hasQuotaRemaining($user, 'chat')) {
            $usage = AIQuotaService::getUsage($user);
            $limit = $usage['chat']['limit'] ?? 5;
            $this->telegram->sendText(
                $dto->chatId,
                "🤖 Você usou suas {$limit} mensagens de IA gratuitas este mês. "
                    . "Faça upgrade para o Pro e tenha IA ilimitada: https://lukrato.com.br/billing"
            );
            $msgRecord->markProcessed('quota_exceeded');
            return;
        }

        // Obter/criar conversa do Telegram (para multi-turn e contexto)
        $conversation = $this->getOrCreateConversation($user->id);

        // Salvar mensagem do usuário
        AiChatMessage::create([
            'conversation_id' => $conversation->id,
            'role'            => 'user',
            'content'         => $normalizedBody,
        ]);

        // Coletar contexto financeiro do usuário
        try {
            $contextBuilder = new UserContextBuilder();
            $context = $contextBuilder->build($user->id);
        } catch (\Throwable) {
            $context = [];
        }

        $context = ContextCompressor::compress($context, $normalizedBody);

        // Incluir últimas mensagens como histórico de conversa
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

        // Delegar para AIService com pipeline completa (IntentRouter → Handler)
        $ai = new AIService();

        $request = new AIRequestDTO(
            userId: $user->id,
            message: $normalizedBody,
            context: $context,
            channel: AIChannel::TELEGRAM,
        );

        $response = $ai->dispatch($request);

        // Salvar resposta do assistente
        AiChatMessage::create([
            'conversation_id' => $conversation->id,
            'role'            => 'assistant',
            'content'         => $response->message,
            'tokens_used'     => $response->tokensUsed ?: null,
            'intent'          => $response->intent?->value,
        ]);

        // Atualizar updated_at da conversa
        $conversation->touch();

        // Tratar resposta baseada no tipo de ação retornada
        $this->sendAiResponse($dto->chatId, $response, $msgRecord);
    }

    /**
     * Envia resposta da IA para o Telegram, tratando diferentes tipos de retorno.
     */
    private function sendAiResponse(
        string $chatId,
        \Application\DTO\AI\AIResponseDTO $response,
        TelegramMessage $msgRecord,
    ): void {
        $intent = $response->intent?->value ?? 'chat';

        // Verificar se a resposta precisa de confirmação (PendingAiAction criado)
        $pendingId = $response->data['pending_id'] ?? $response->data['pending_action_id'] ?? null;
        if (!empty($pendingId)) {
            // Enviar com botões Sim/Não
            $chunks = TelegramResponseFormatter::format($response->message);
            $lastIndex = count($chunks) - 1;

            foreach ($chunks as $i => $chunk) {
                if ($i === $lastIndex) {
                    $this->telegram->sendConfirmationButtons($chatId, $chunk);
                } else {
                    $this->telegram->sendText($chatId, $chunk);
                }
            }
            $msgRecord->markProcessed($intent . '_pending');
            return;
        }

        // Verificar se a resposta tem opções para seleção (awaiting_selection)
        if (!empty($response->data['options']) && ($response->data['action'] ?? '') === 'awaiting_selection') {
            $this->sendSelectionButtons($chatId, $response->message, $response->data['options']);
            $msgRecord->markProcessed($intent . '_awaiting_selection');
            return;
        }

        // Resposta normal — formatar e enviar (com split se necessário)
        $chunks = TelegramResponseFormatter::format($response->message);

        foreach ($chunks as $chunk) {
            $this->telegram->sendText($chatId, $chunk);
        }

        $msgRecord->markProcessed($intent);
    }

    /**
     * Transação detectada → criar PendingAiAction + pedir confirmação com botões inline.
     * Unificado: usa PendingAiAction (mesmo modelo do web chat) com TTL de 24h.
     */
    private function handleTransactionExtraction(
        TelegramMessageDTO $dto,
        \Application\Models\Usuario $user,
        array $extracted,
        TelegramMessage $msgRecord,
    ): void {
        // Categorizar via rule engine
        $category = CategoryRuleEngine::match(
            $extracted['descricao'],
            $user->id,
        );

        // Montar payload unificado (mesmo formato do EntityCreationHandler)
        $payload = [
            'descricao'        => $extracted['descricao'],
            'valor'            => $extracted['valor'],
            'tipo'             => $extracted['tipo'],
            'data'             => $extracted['data'],
            'categoria_id'     => $category['categoria_id'] ?? null,
            'subcategoria_id'  => $category['subcategoria_id'] ?? null,
            'categoria_nome'   => $category['categoria'] ?? null,
            'subcategoria_nome' => $category['subcategoria'] ?? null,
            'origem'           => 'telegram',
            'pago'             => true,
        ];

        // Incluir forma_pagamento e parcelamento se detectados
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

        // Criar PendingAiAction (unificado) com TTL de 24h
        PendingAiAction::create([
            'user_id'     => $user->id,
            'action_type' => 'create_lancamento',
            'payload'     => $payload,
            'status'      => 'awaiting_confirm',
            'expires_at'  => now()->addHours(24),
        ]);

        // Montar texto de confirmação
        $tipo      = $extracted['tipo'] === 'receita' ? '💰 Receita' : '💸 Despesa';
        $formatted = 'R$ ' . number_format($extracted['valor'], 2, ',', '.');
        $catStr    = '';
        if ($category !== null) {
            $catStr = "\n📁 " . $category['categoria'];
            if (!empty($category['subcategoria'])) {
                $catStr .= " > " . $category['subcategoria'];
            }
        }

        $text = "Entendi! Registrar?\n\n"
            . "{$tipo}: {$extracted['descricao']}\n"
            . "💵 {$formatted}{$catStr}";

        $this->telegram->sendConfirmationButtons(
            $dto->chatId,
            $text,
            'confirm_yes',
            'confirm_no',
        );

        $msgRecord->markProcessed('transaction_pending');
    }

    // ─── Helper Methods ───────────────────────────────────────

    /**
     * Obtém ou cria uma AiConversation para o Telegram do usuário.
     * Reutiliza a conversa ativa mais recente (criada nas últimas 24h) para manter contexto.
     */
    private function getOrCreateConversation(int $userId): AiConversation
    {
        // Buscar conversa recente do Telegram (últimas 24h)
        $conversation = AiConversation::where('user_id', $userId)
            ->where('titulo', 'Telegram')
            ->where('updated_at', '>=', now()->subHours(24))
            ->orderByDesc('updated_at')
            ->first();

        if ($conversation !== null) {
            return $conversation;
        }

        // Criar nova conversa
        return AiConversation::create([
            'user_id' => $userId,
            'titulo'  => 'Telegram',
        ]);
    }

    /**
     * Resolve a conta para um lançamento de forma inteligente.
     *
     * @return int|array|null  int=conta_id, array=['needs_selection'=>true, 'contas'=>...], null=sem contas
     */
    private function resolveAccount(\Application\Models\Usuario $user, array $payload): int|array|null
    {
        $contaRepo = new ContaRepository();
        $contas = $contaRepo->findActive($user->id);

        if ($contas->isEmpty()) {
            return null;
        }

        // 1 conta → auto-selecionar
        if ($contas->count() === 1) {
            return $contas->first()->id;
        }

        // Tentar match por nome de banco mencionado na transação
        $nomeCartao = $payload['nome_cartao'] ?? null;
        $descricao = mb_strtolower($payload['descricao'] ?? '');

        if ($nomeCartao !== null) {
            $nomeCartaoLower = mb_strtolower($nomeCartao);

            foreach ($contas as $conta) {
                // Match por nome da conta
                if (str_contains(mb_strtolower($conta->nome), $nomeCartaoLower)) {
                    return $conta->id;
                }
                // Match por campo instituição
                if ($conta->instituicao && str_contains(mb_strtolower($conta->instituicao), $nomeCartaoLower)) {
                    return $conta->id;
                }
                // Match por instituição financeira vinculada
                if ($conta->instituicaoFinanceira && str_contains(
                    mb_strtolower($conta->instituicaoFinanceira->nome ?? ''),
                    $nomeCartaoLower
                )) {
                    return $conta->id;
                }
            }
        }

        // Tentar match pela descrição da transação
        if ($descricao !== '') {
            foreach ($contas as $conta) {
                $nomeLower = mb_strtolower($conta->nome);
                if (str_contains($descricao, $nomeLower) || str_contains($nomeLower, $descricao)) {
                    return $conta->id;
                }
            }
        }

        // Múltiplas contas, sem match → pedir seleção
        return [
            'needs_selection' => true,
            'contas' => $contas,
        ];
    }

    /**
     * Envia botões inline para seleção de conta.
     */
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

            // Máximo 2 botões por linha para legibilidade
            if (count($row) >= 2) {
                $rows[] = $row;
                $row = [];
            }
        }

        if (!empty($row)) {
            $rows[] = $row;
        }

        // Adicionar botão de cancelar
        $rows[] = [['text' => '❌ Cancelar', 'callback_data' => 'confirm_no']];

        $this->telegram->sendInlineKeyboard(
            $chatId,
            "🏦 Em qual conta deseja registrar?",
            $rows,
        );
    }

    /**
     * Envia botões inline para seleção de opções (multi-turn: cartão, categoria, etc).
     */
    private function sendSelectionButtons(string $chatId, string $text, array $options): void
    {
        $rows = [];

        foreach ($options as $i => $option) {
            $label = $option['nome'] ?? $option['name'] ?? $option['titulo'] ?? "Opção " . ($i + 1);
            $rows[] = [[
                'text' => $label,
                'callback_data' => "select_option_{$i}",
            ]];
        }

        // Adicionar botão de cancelar
        $rows[] = [['text' => '❌ Cancelar', 'callback_data' => 'confirm_no']];

        $chunks = TelegramResponseFormatter::format($text);
        $lastIndex = count($chunks) - 1;

        foreach ($chunks as $j => $chunk) {
            if ($j === $lastIndex) {
                $this->telegram->sendInlineKeyboard($chatId, $chunk, $rows);
            } else {
                $this->telegram->sendText($chatId, $chunk);
            }
        }
    }

    private function allowIncomingSender(string $sender, ?string $messageId = null): bool
    {
        $limiter = new AIRateLimiter();

        return $limiter->allow(
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
}
