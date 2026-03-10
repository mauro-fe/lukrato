<?php

declare(strict_types=1);

namespace Application\Controllers\Api\AI;

use Application\Controllers\BaseController;
use Application\DTO\AI\AIRequestDTO;
use Application\DTO\AI\WhatsAppMessageDTO;
use Application\Enums\AI\AIChannel;
use Application\Enums\AI\IntentType;
use Application\Models\Lancamento;
use Application\Models\PendingWhatsAppTransaction;
use Application\Models\WhatsAppMessage;
use Application\Services\AI\AIService;
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
                    . "Acesse seu painel em lukrato.com → Configurações → WhatsApp para vincular."
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
     */
    private function handleConfirmationReply(
        WhatsAppMessageDTO $dto,
        \Application\Models\Usuario $user,
        WhatsAppMessage $msgRecord,
    ): void {
        // Usar transação com lock para evitar duplicatas por webhooks concorrentes
        DB::transaction(function () use ($dto, $user, $msgRecord) {
            $pending = PendingWhatsAppTransaction::query()
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
                $lancamento = $this->createLancamento($pending, $user);
                $pending->confirm();

                $formatted = 'R$ ' . number_format($pending->valor, 2, ',', '.');
                $this->whatsapp->sendText(
                    $dto->fromPhone,
                    "✅ Lançamento registrado!\n\n"
                        . "📝 {$pending->descricao}\n"
                        . "💰 {$formatted}\n"
                        . ($pending->categoria_nome ? "📁 {$pending->categoria_nome}" : '')
                );
                $msgRecord->markProcessed('transaction_confirmed');
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
     * Transação detectada → criar pendente + pedir confirmação com botões.
     */
    private function handleTransactionExtraction(
        WhatsAppMessageDTO $dto,
        \Application\Models\Usuario $user,
        array $extracted,
        WhatsAppMessage $msgRecord,
    ): void {
        // Categorizar via rule engine
        $category = \Application\Services\AI\Rules\CategoryRuleEngine::match(
            $extracted['descricao'],
            $user->id,
        );

        // Criar registro pendente
        $pending = PendingWhatsAppTransaction::create([
            'user_id'            => $user->id,
            'wa_message_id'      => $dto->waMessageId,
            'descricao'          => $extracted['descricao'],
            'valor'              => $extracted['valor'],
            'tipo'               => $extracted['tipo'],
            'data'               => $extracted['data'],
            'categoria_id'       => $category['categoria_id'] ?? null,
            'subcategoria_id'    => $category['subcategoria_id'] ?? null,
            'categoria_nome'     => $category['categoria'] ?? null,
            'subcategoria_nome'  => $category['subcategoria'] ?? null,
            'status'             => 'awaiting_confirm',
            'expires_at'         => now()->addHours(24),
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

    /**
     * Cria o lançamento real a partir de uma transação pendente confirmada.
     */
    private function createLancamento(PendingWhatsAppTransaction $pending, \Application\Models\Usuario $user): Lancamento
    {
        return Lancamento::create([
            'user_id'          => $user->id,
            'descricao'        => $pending->descricao,
            'valor'            => $pending->valor,
            'tipo'             => $pending->tipo,
            'data'             => $pending->data,
            'categoria_id'     => $pending->categoria_id,
            'subcategoria_id'  => $pending->subcategoria_id,
            'pago'             => true,
            'origem'           => 'whatsapp',
        ]);
    }
}
