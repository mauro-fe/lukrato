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
use Application\Services\AI\AiLogService;
use Application\Services\AI\IntentRules\ConfirmationIntentRule;
use Application\Services\AI\Media\MediaAsset;
use Application\Services\AI\Media\MediaProcessingResult;
use Application\Services\AI\Media\MediaRouterService;
use Application\Services\AI\Media\ReceiptAnalysisResult;
use Application\Services\AI\NLP\NumberNormalizer;
use Application\Services\AI\NLP\TextNormalizer;
use Application\Services\AI\Rules\CategoryRuleEngine;
use Application\Services\AI\Security\AIRateLimiter;
use Application\Services\AI\TransactionDetectorService;
use Application\Services\AI\WhatsApp\WhatsAppMediaDownloader;
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
 * Padrão: webhooks válidos retornam 200; assinaturas inválidas recebem 403.
 * Idempotência: via whatsapp_messages.wa_message_id (UNIQUE).
 * Canal: WhatsApp é tratado como "mais um canal" → usa AIService.dispatch() normalmente.
 */
class WhatsAppWebhookController extends BaseController
{
    private const SENDER_LIMIT = 30;
    private const SENDER_WINDOW_SECONDS = 60;

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
     * Webhooks válidos retornam 200 para a Meta; assinaturas inválidas recebem 403.
     */
    public function receive(): void
    {
        $rawBody = file_get_contents('php://input');
        $rawBody = is_string($rawBody) ? $rawBody : '';

        if (!$this->isValidWebhookSignature($rawBody)) {
            LogService::persist(
                LogLevel::WARNING,
                LogCategory::WEBHOOK,
                'WhatsApp webhook signature inválida',
                ['ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'],
            );

            http_response_code(403);
            echo 'Forbidden';
            return;
        }

        $payload = json_decode($rawBody, true);

        // Meta recebe 200 apenas para webhooks válidos
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
            'media_file_id'     => $dto->mediaId,
            'media_mime_type'   => $dto->mimeType,
            'media_file_size'   => $dto->fileSize,
            'media_filename'    => $dto->filename,
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
        if (!$this->allowIncomingSender($dto->fromPhone, $dto->waMessageId)) {
            $msgRecord->markIgnored();
            return;
        }

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

        if ($dto->isMedia()) {
            $this->handleMediaMessage($dto, $user, $msgRecord);
            return;
        }

        // 3. Verificar se é resposta de confirmação a uma transação pendente
        if ($this->shouldHandleConfirmationReply($dto, $user->id)) {
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

            if (ConfirmationIntentRule::isAffirmative($dto->body)) {
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

                // Auto-selecionar conta: buscar match por nome do banco na mensagem
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

                    // Smart account resolution: match pelo nome do banco/cartão no payload
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

    private function shouldHandleConfirmationReply(WhatsAppMessageDTO $dto, int $userId): bool
    {
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

    private function handleMediaMessage(
        WhatsAppMessageDTO $dto,
        \Application\Models\Usuario $user,
        WhatsAppMessage $msgRecord,
    ): void {
        if (!AIQuotaService::hasQuotaRemaining($user, 'chat')) {
            $usage = AIQuotaService::getUsage($user);
            $limit = $usage['chat']['limit'] ?? 5;
            $this->whatsapp->sendText(
                $dto->fromPhone,
                "🤖 Você usou suas {$limit} mensagens de IA gratuitas este mês. "
                    . "Faça upgrade para o Pro: https://lukrato.com.br/billing"
            );
            $msgRecord->markProcessed('quota_exceeded');
            return;
        }

        $statusText = $dto->isAudio()
            ? "🎙️ Transcrevendo áudio..."
            : ($dto->isVideo() ? "🎬 Processando vídeo..." : "📎 Analisando arquivo...");
        $this->whatsapp->sendText($dto->fromPhone, $statusText);

        $downloader = new WhatsAppMediaDownloader();
        $fileData = $downloader->downloadByMediaId((string) $dto->mediaId, $dto->filename);

        if ($fileData === null) {
            $this->whatsapp->sendText($dto->fromPhone, "⚠️ Não consegui baixar o arquivo. Tente novamente.");
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

        $result = (new MediaRouterService())->process($asset);
        $this->logMediaProcessing($user->id, 'whatsapp', $dto->mediaId, $dto->caption, $result);

        if ($result->isUnsupported()) {
            $this->whatsapp->sendText(
                $dto->fromPhone,
                "⚠️ Ainda não consigo processar esse tipo de arquivo. Envie imagem, PDF, áudio ou vídeo curto."
            );
            $msgRecord->markProcessed('unsupported_media');
            return;
        }

        if (!$result->success) {
            $this->whatsapp->sendText(
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
                $this->whatsapp->sendText(
                    $dto->fromPhone,
                    "📎 {$desc}\n\nPara registrar uma transação, envie um comprovante, nota fiscal, PDF ou descreva o lançamento por texto."
                );
                $msgRecord->markProcessed('media_not_financial');
                return;
            }

            $transactionData = $receipt->toTransactionData();
            if ($transactionData['valor'] <= 0) {
                $desc = $transactionData['descricao'];
                $this->whatsapp->sendText(
                    $dto->fromPhone,
                    "📎 Vi um comprovante de {$desc}, mas nao consegui identificar o valor. "
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
     * Trata mensagem normal: detectar intent, processar, responder.
     * Normaliza texto e números antes do processamento.
     */
    private function handleNormalMessage(
        WhatsAppMessageDTO $dto,
        \Application\Models\Usuario $user,
        WhatsAppMessage $msgRecord,
    ): void {
        // Pré-processar: expandir abreviações WhatsApp e normalizar números BR
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
            message: $normalizedBody,
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
            $extracted['categoria_contexto'] ?? null
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

    private function isValidWebhookSignature(string $rawBody): bool
    {
        $appSecret = WhatsAppService::getAppSecret();
        if ($appSecret === '') {
            return true;
        }

        $signature = (string) ($_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '');
        if ($signature === '' || !str_starts_with($signature, 'sha256=')) {
            return false;
        }

        $expected = 'sha256=' . hash_hmac('sha256', $rawBody, $appSecret);
        return hash_equals($expected, $signature);
    }

    private function allowIncomingSender(string $sender, ?string $messageId = null): bool
    {
        $limiter = new AIRateLimiter();

        return $limiter->allow(
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
}
