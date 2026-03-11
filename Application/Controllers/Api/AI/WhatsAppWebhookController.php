<?php

declare(strict_types=1);

namespace Application\Controllers\Api\AI;

use Application\Controllers\BaseController;
use Application\DTO\AI\AIRequestDTO;
use Application\DTO\AI\WhatsAppMessageDTO;
use Application\Enums\AI\AIChannel;
use Application\Models\PendingAiAction;
use Application\Models\WhatsAppMessage;
use Application\Repositories\ContaRepository;
use Application\Services\AI\AIService;
use Application\Services\AI\AIQuotaService;
use Application\Services\AI\Actions\ActionRegistry;
use Application\Services\AI\Rules\CategoryRuleEngine;
use Application\Services\AI\TransactionDetectorService;
use Application\Services\AI\WhatsApp\WhatsAppService;
use Application\Services\AI\WhatsApp\WhatsAppUserResolver;
use Application\Services\Infrastructure\LogService;
use Application\Enums\LogLevel;
use Application\Enums\LogCategory;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Controller para o webhook do WhatsApp (Meta Cloud API).
 *
 * Endpoints:
 *  GET  /api/webhook/whatsapp  → Verificação do webhook (hub.challenge)
 *  POST /api/webhook/whatsapp  → Recepção de mensagens
 *
 * Padrão: SEMPRE retorna 200 para a Meta, independente de erro interno.
 * Idempotência: via whatsapp_messages.wa_message_id (UNIQUE).
 * Canal: WhatsApp é tratado como "mais um canal" → usa AIService.dispatch() normalmente.
 */
class WhatsAppWebhookController extends BaseController
{
    private WhatsAppService $whatsapp;

    public function __construct()
    {
        parent::__construct();
        $this->whatsapp = new WhatsAppService();
    }

    // ─── Webhook Verification (GET) ──────────────────────────

    /**
     * Meta envia GET com hub.mode, hub.verify_token, hub.challenge.
     * Retornar hub.challenge se o token bater.
     */
    public function verify(): void
    {
        $mode      = $_GET['hub_mode']         ?? $_GET['hub.mode']         ?? '';
        $token     = $_GET['hub_verify_token']  ?? $_GET['hub.verify_token']  ?? '';
        $challenge = $_GET['hub_challenge']     ?? $_GET['hub.challenge']     ?? '';

        $expectedToken = WhatsAppService::getVerifyToken();

        if ($mode === 'subscribe' && $token === $expectedToken && $expectedToken !== '') {
            http_response_code(200);
            echo $challenge;
            return;
        }

        LogService::persist(
            LogLevel::WARNING,
            LogCategory::WEBHOOK,
            'WhatsApp webhook verify falhou',
            ['mode' => $mode, 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'],
        );

        http_response_code(403);
        echo 'Forbidden';
    }

    // ─── Message Reception (POST) ─────────────────────────────

    /**
     * Recebe mensagens do WhatsApp.
     * SEMPRE retorna 200 para a Meta.
     */
    public function receive(): void
    {
        $rawBody = file_get_contents('php://input');
        $payload = json_decode($rawBody, true);

        // Sempre 200 para a Meta
        http_response_code(200);

        if (!is_array($payload)) {
            echo 'OK';
            return;
        }

        // Extrair entry → changes → value
        $entry = $payload['entry'][0]['changes'][0]['value'] ?? null;

        if ($entry === null) {
            echo 'OK';
            return;
        }

        // Status updates (delivered, read) → ignorar
        if (isset($entry['statuses'])) {
            echo 'OK';
            return;
        }

        // Parsear mensagem via DTO
        $dto = WhatsAppMessageDTO::fromMetaPayload($entry);

        if ($dto === null) {
            echo 'OK';
            return;
        }

        // Idempotência: já processamos esta mensagem?
        if (WhatsAppMessage::alreadyProcessed($dto->waMessageId)) {
            echo 'OK';
            return;
        }

        // Registrar mensagem recebida
        $msgRecord = WhatsAppMessage::create([
            'wa_message_id'     => $dto->waMessageId,
            'from_phone'        => $dto->fromPhone,
            'direction'         => 'incoming',
            'type'              => $dto->type,
            'body'              => $dto->body,
            'metadata'          => $dto->rawPayload,
            'processing_status' => 'received',
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
                    'phone'         => $dto->fromPhone,
                    'error'         => $e->getMessage(),
                ],
            );
        }

        echo 'OK';
    }

    // ─── Processing Pipeline ──────────────────────────────────

    /**
     * Pipeline principal de processamento.
     */
    private function processMessage(WhatsAppMessageDTO $dto, WhatsAppMessage $msgRecord): void
    {
        // 1. Marcar como lida
        $this->whatsapp->markAsRead($dto->waMessageId);

        // 2. Resolver usuário pelo phone
        $user = WhatsAppUserResolver::resolve($dto->fromPhone);

        if ($user === null) {
            $this->whatsapp->sendText(
                $dto->fromPhone,
                "Olá! Você ainda não vinculou seu WhatsApp ao Lukrato.\n\n"
                    . "Acesse seu painel em lukrato.com.br → Configurações → WhatsApp para vincular."
            );
            $msgRecord->markIgnored();
            return;
        }

        $msgRecord->update(['user_id' => $user->id]);

        // 3. Verificar se é resposta de confirmação a uma transação pendente
        if ($dto->isConfirmationReply()) {
            $this->handleConfirmationReply($dto, $user, $msgRecord);
            return;
        }

        // 4. Processar como mensagem normal via AIService (WhatsApp = mais um canal)
        $this->handleNormalMessage($dto, $user, $msgRecord);
    }

    /**
     * Trata resposta Sim/Não a uma transação pendente.
     * Unificado: usa PendingAiAction + ActionRegistry (mesmo pipeline do web chat).
     */
    private function handleConfirmationReply(
        WhatsAppMessageDTO $dto,
        \Application\Models\Usuario $user,
        WhatsAppMessage $msgRecord,
    ): void {
        DB::transaction(function () use ($dto, $user, $msgRecord) {
            $pending = PendingAiAction::query()
                ->where('user_id', $user->id)
                ->awaiting()
                ->orderByDesc('created_at')
                ->lockForUpdate()
                ->first();

            if ($pending === null) {
                $this->whatsapp->sendText(
                    $dto->fromPhone,
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
                    $this->whatsapp->sendText($dto->fromPhone, "⚠️ Tipo de ação desconhecido.");
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
                        $this->whatsapp->sendText(
                            $dto->fromPhone,
                            "⚠️ Você precisa ter pelo menos uma conta cadastrada no Lukrato para registrar lançamentos."
                        );
                        $msgRecord->markProcessed('confirmation_no_account');
                        return;
                    }

                    // WhatsApp: auto-seleciona a primeira conta ativa
                    $payload['conta_id'] = $contas->first()->id;
                    $pending->update(['payload' => $payload]);
                }

                try {
                    $result = $action->execute($user->id, $payload);

                    if (!$result->success) {
                        $pending->reject();
                        $this->whatsapp->sendText($dto->fromPhone, "⚠️ {$result->message}");
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
                    $this->whatsapp->sendText(
                        $dto->fromPhone,
                        "✅ Lançamento registrado!\n\n"
                            . "📝 {$payload['descricao']}\n"
                            . "💰 {$formatted}{$catStr}"
                    );
                    $msgRecord->markProcessed('transaction_confirmed');
                } catch (\Throwable $e) {
                    $pending->reject();
                    $this->whatsapp->sendText($dto->fromPhone, "⚠️ Erro ao registrar: " . $e->getMessage());
                    $msgRecord->markProcessed('confirmation_error');
                }
            } else {
                $pending->reject();
                $this->whatsapp->sendText($dto->fromPhone, "❌ Transação cancelada.");
                $msgRecord->markProcessed('transaction_rejected');
            }
        });
    }

    /**
     * Trata mensagem normal: detectar intent, processar, responder.
     */
    private function handleNormalMessage(
        WhatsAppMessageDTO $dto,
        \Application\Models\Usuario $user,
        WhatsAppMessage $msgRecord,
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
            $this->whatsapp->sendText(
                $dto->fromPhone,
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
            channel: AIChannel::WHATSAPP,
        );

        $response = $ai->dispatch($request);

        $this->whatsapp->sendText($dto->fromPhone, $response->message);
        $msgRecord->markProcessed($response->intent?->value ?? 'chat');
    }

    /**
     * Transação detectada → criar PendingAiAction + pedir confirmação com botões.
     * Unificado: usa PendingAiAction (mesmo modelo do web chat) com TTL de 24h.
     */
    private function handleTransactionExtraction(
        WhatsAppMessageDTO $dto,
        \Application\Models\Usuario $user,
        array $extracted,
        WhatsAppMessage $msgRecord,
    ): void {
        // Categorizar via rule engine (agora inclui regras personalizadas do usuário)
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
            'origem'           => 'whatsapp',
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

        // Criar PendingAiAction (unificado) com TTL de 24h para WhatsApp
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
            . "💵 {$formatted}{$catStr}\n\n"
            . "Responda Sim para confirmar ou Não para cancelar.";

        $this->whatsapp->sendConfirmationButtons(
            $dto->fromPhone,
            $text,
            'confirm_yes',
            'confirm_no',
        );

        $msgRecord->markProcessed('transaction_pending');
    }
}
