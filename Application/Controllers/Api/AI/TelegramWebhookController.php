<?php

declare(strict_types=1);

namespace Application\Controllers\Api\AI;

use Application\Controllers\BaseController;
use Application\DTO\AI\AIRequestDTO;
use Application\DTO\AI\TelegramMessageDTO;
use Application\Enums\AI\AIChannel;
use Application\Models\PendingAiAction;
use Application\Models\TelegramMessage;
use Application\Repositories\ContaRepository;
use Application\Services\AI\AIService;
use Application\Services\AI\AIQuotaService;
use Application\Services\AI\Actions\ActionRegistry;
use Application\Services\AI\Rules\CategoryRuleEngine;
use Application\Services\AI\TransactionDetectorService;
use Application\Services\AI\Telegram\TelegramService;
use Application\Services\AI\Telegram\TelegramUserResolver;
use Application\Services\Infrastructure\LogService;
use Application\Enums\LogLevel;
use Application\Enums\LogCategory;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Controller para o webhook do Telegram Bot API.
 *
 * Endpoint:
 *  POST /api/webhook/telegram  → Recepção de updates
 *
 * Padrão: SEMPRE retorna 200 para o Telegram, independente de erro interno.
 * Idempotência: via telegram_messages.tg_update_id (UNIQUE).
 * Segurança: valida header X-Telegram-Bot-Api-Secret-Token.
 * Canal: Telegram é tratado como "mais um canal" → usa AIService.dispatch() normalmente.
 */
class TelegramWebhookController extends BaseController
{
    private TelegramService $telegram;

    public function __construct()
    {
        parent::__construct();
        $this->telegram = new TelegramService();
    }

    // ─── Webhook Reception (POST) ─────────────────────────────

    /**
     * Recebe updates do Telegram.
     * SEMPRE retorna 200 para o Telegram.
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

        // Verificar se é resposta de confirmação a uma transação pendente
        if ($dto->isConfirmationReply()) {
            $this->handleConfirmationReply($dto, $user, $msgRecord);
            return;
        }

        // Processar como mensagem normal via AIService
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
                        . "📱 Com este bot você pode registrar receitas e despesas diretamente pelo Telegram.\n\n"
                        . "🔗 Para começar, vincule sua conta:\n"
                        . "1. Acesse <b>lukrato.com.br</b> → Perfil → Telegram\n"
                        . "2. Clique em \"Vincular Telegram\"\n"
                        . "3. Envie o código de 6 dígitos aqui\n\n"
                        . "Pronto! Depois é só mandar mensagens como:\n"
                        . "💬 <i>\"almoco 35\"</i>\n"
                        . "💬 <i>\"recebi salário 5000\"</i>"
                );
                $msgRecord->markProcessed('command_start');
                break;

            case 'help':
                $this->telegram->sendText(
                    $dto->chatId,
                    "📖 <b>Como usar o Lukrato Bot</b>\n\n"
                        . "💬 Envie uma mensagem descrevendo a transação:\n"
                        . "• <i>\"almoco 35\"</i> → despesa de R$ 35\n"
                        . "• <i>\"uber 22.50\"</i> → despesa de R$ 22,50\n"
                        . "• <i>\"recebi salário 5000\"</i> → receita de R$ 5.000\n"
                        . "• <i>\"netflix 55.90 cartão nubank\"</i> → no cartão\n\n"
                        . "✅ Eu vou identificar o valor, tipo e categoria automaticamente.\n"
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

                // Auto-selecionar conta se não definida
                if ($pending->action_type === 'create_lancamento' && empty($payload['conta_id'])) {
                    $contaRepo = new ContaRepository();
                    $contas = $contaRepo->findActive($user->id);

                    if ($contas->isEmpty()) {
                        $pending->reject();
                        $this->telegram->sendText(
                            $dto->chatId,
                            "⚠️ Você precisa ter pelo menos uma conta cadastrada no Lukrato para registrar lançamentos."
                        );
                        $msgRecord->markProcessed('confirmation_no_account');
                        return;
                    }

                    // Telegram: auto-seleciona a primeira conta ativa
                    $payload['conta_id'] = $contas->first()->id;
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
     * Trata mensagem normal: detectar intent, processar, responder.
     */
    private function handleNormalMessage(
        TelegramMessageDTO $dto,
        \Application\Models\Usuario $user,
        TelegramMessage $msgRecord,
    ): void {
        // Tentar extração de transação primeiro (regex, 0 tokens)
        $extracted = TransactionDetectorService::extract($dto->body);

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

        // Delegar para AIService como qualquer outro canal
        $ai = new AIService();

        $request = AIRequestDTO::chat(
            userId: $user->id,
            message: $dto->body,
            channel: AIChannel::TELEGRAM,
        );

        $response = $ai->dispatch($request);

        $this->telegram->sendText($dto->chatId, $response->message);
        $msgRecord->markProcessed($response->intent?->value ?? 'chat');
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
}
