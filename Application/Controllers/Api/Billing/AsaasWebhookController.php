<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Billing;

use Application\Controllers\ApiController;
use Application\Core\Response;
use Application\Models\AssinaturaUsuario;
use Application\Models\LogWebhookCobranca;
use Application\Models\Usuario;
use Application\Services\Billing\AsaasService;
use Application\Services\Infrastructure\LogService;
use Application\Services\Communication\MailService;
use Application\Enums\LogLevel;
use Application\Enums\LogCategory;
use Illuminate\Database\Capsule\Manager as DB;

class AsaasWebhookController extends ApiController
{
    private AsaasService $asaas;
    private MailService $mailService;

    public function __construct(?AsaasService $asaas = null, ?MailService $mailService = null)
    {
        parent::__construct();
        $this->asaas = $this->resolveOrCreate($asaas, AsaasService::class);
        $this->mailService = $this->resolveOrCreate($mailService, MailService::class);
    }

    /**
     * Endpoint de teste - APENAS para desenvolvimento
     * Em produção retorna 404 para não expor informações
     */
    public function test(): Response
    {
        // Em produção, não revelar que o endpoint existe
        if (defined('APP_ENV') && APP_ENV === 'production') {
            return $this->plainTextResponse('Not Found', 404);
        }

        // Em desenvolvimento, permitir verificar se está funcionando
        return $this->plainTextResponse('Webhook OK (dev only)');
    }

    public function receive(): Response
    {
        $headers = $this->readRequestHeaders();
        $rawBody = $this->readRawBody();

        // 🔐 Validação de segurança (NUNCA retornar erro externo)
        if (!$this->asaas->validateWebhookRequest($headers, $rawBody)) {
            LogService::persist(
                LogLevel::WARNING,
                LogCategory::WEBHOOK,
                'Webhook Asaas rejeitado por validação',
                [
                    'headers' => array_keys($headers),
                    'ip' => $this->requestIp(),
                ],
            );

            return $this->plainTextResponse('OK');
        }

        $payload = json_decode($rawBody, true);

        if (!is_array($payload)) {
            LogService::persist(
                LogLevel::WARNING,
                LogCategory::WEBHOOK,
                'Webhook Asaas com payload inválido',
                [
                    'payload_size' => strlen((string) $rawBody),
                    'payload_hash' => hash('sha256', (string) $rawBody),
                ],
            );

            return $this->plainTextResponse('OK');
        }

        $event = $payload['event'] ?? null;

        if (!$event) {
            return $this->plainTextResponse('OK');
        }

        // 🔁 IDEMPOTÊNCIA
        $eventId = $payload['id'] ?? uniqid();
        $resourceId = $payload['payment']['id']
            ?? $payload['subscription']['id']
            ?? 'unknown';

        $idempotencyKey = hash('sha256', "{$event}_{$eventId}_{$resourceId}");

        if (DB::table('webhook_idempotencia')
            ->where('idempotency_key', $idempotencyKey)
            ->exists()
        ) {
            LogService::info('Webhook Asaas ignorado (idempotente)', [
                'event' => $event,
                'key' => $idempotencyKey,
            ]);

            return $this->plainTextResponse('OK');
        }

        // 📦 Log bruto
        LogWebhookCobranca::create([
            'provedor' => 'asaas',
            'tipo_evento' => $event,
            'payload' => $this->buildWebhookPayloadSummary($payload, (string) $rawBody),
        ]);

        try {
            DB::beginTransaction();

            DB::table('webhook_idempotencia')->insert([
                'idempotency_key' => $idempotencyKey,
                'event_type' => $event,
                'payload_hash' => hash('sha256', $rawBody),
                'processed_at' => now(),
                'created_at' => now(),
            ]);

            if (str_starts_with($event, 'SUBSCRIPTION_')) {
                $this->handleSubscriptionEvent($event, $payload);
            }

            if (str_starts_with($event, 'PAYMENT_')) {
                $this->handlePaymentEvent($event, $payload);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            LogService::captureException($e, LogCategory::WEBHOOK, [
                'event' => $event,
                'resource_id' => $resourceId,
            ]);
        }

        return $this->plainTextResponse('OK');
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function handleSubscriptionEvent(string $event, array $payload): void
    {
        $subscription = $payload['subscription'] ?? null;

        if (!$subscription || empty($subscription['id'])) {
            return;
        }

        $asaasId = $subscription['id'];
        $statusAsaas = $subscription['status'] ?? null;
        $nextDueDate = $subscription['nextDueDate'] ?? null;

        $novoStatus = $this->mapSubscriptionStatus($statusAsaas);

        $assinatura = AssinaturaUsuario::where('external_subscription_id', $asaasId)
            ->lockForUpdate()
            ->latest('id')
            ->first();

        if (!$assinatura) {
            LogService::persist(
                LogLevel::WARNING,
                LogCategory::SUBSCRIPTION,
                'Assinatura não encontrada para webhook',
                ['asaas_id' => $asaasId],
            );
            return;
        }

        $statusAnterior = $assinatura->status;

        if ($novoStatus) {
            $assinatura->status = $novoStatus;
        }

        if ($nextDueDate) {
            $assinatura->renova_em = $nextDueDate;
        }

        $assinatura->save();

        LogService::info('Assinatura atualizada via webhook', [
            'assinatura_id' => $assinatura->id,
            'user_id' => $assinatura->user_id,
            'status_anterior' => $statusAnterior,
            'status_novo' => $novoStatus,
            'event' => $event,
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function handlePaymentEvent(string $event, array $payload): void
    {
        $payment = $payload['payment'] ?? null;

        if (!$payment || empty($payment['id'])) {
            return;
        }

        $paymentStatus = $payment['status'] ?? null;
        $paidStatuses = ['RECEIVED', 'CONFIRMED', 'RECEIVED_IN_CASH'];
        $isPaid = in_array($paymentStatus, $paidStatuses, true);

        // ────────────────────────────────────────────────────────────
        // CASO 1: Pagamento vinculado a uma SUBSCRIPTION (Cartão de crédito recorrente)
        // ────────────────────────────────────────────────────────────
        if (!empty($payment['subscription'])) {
            if (!$isPaid) {
                return;
            }

            $assinatura = AssinaturaUsuario::where(
                'external_subscription_id',
                $payment['subscription']
            )
                ->lockForUpdate()
                ->latest('id')
                ->first();

            if (!$assinatura) {
                LogService::persist(
                    LogLevel::WARNING,
                    LogCategory::SUBSCRIPTION,
                    'Assinatura não encontrada para pagamento de subscription',
                    ['subscription_id' => $payment['subscription'], 'payment_id' => $payment['id']],
                );
                return;
            }

            $this->activateSubscription($assinatura, $payment);
            return;
        }

        // ────────────────────────────────────────────────────────────
        // CASO 2: Pagamento AVULSO (PIX ou Boleto) — sem subscription vinculada
        // ────────────────────────────────────────────────────────────
        $paymentId = $payment['id'];

        $assinatura = AssinaturaUsuario::where('external_payment_id', $paymentId)
            ->lockForUpdate()
            ->latest('id')
            ->first();

        if (!$assinatura) {
            // Pagamento avulso não relacionado a nenhuma assinatura — ignorar
            return;
        }

        if ($isPaid) {
            $this->activateSubscription($assinatura, $payment);
        } elseif (in_array($paymentStatus, ['OVERDUE', 'REFUNDED', 'DELETED', 'REFUND_REQUESTED'], true)) {
            // Pagamento cancelado/expirado — marcar assinatura como expirada
            if ($assinatura->status === AssinaturaUsuario::ST_PENDING) {
                $assinatura->status = AssinaturaUsuario::ST_EXPIRED;
                $assinatura->save();

                LogService::info('Pagamento avulso expirado/cancelado via webhook', [
                    'assinatura_id' => $assinatura->id,
                    'user_id' => $assinatura->user_id,
                    'payment_id' => $paymentId,
                    'payment_status' => $paymentStatus,
                ]);
            }
        }
    }

    /**
     * Ativa uma assinatura após confirmação de pagamento.
     * Usado tanto para pagamentos de subscription (cartão) quanto avulsos (PIX/Boleto).
     */
    /**
     * @param array<string, mixed> $payment
     */
    private function activateSubscription(AssinaturaUsuario $assinatura, array $payment): void
    {
        $statusAnterior = $assinatura->status;
        $isPrimeiraAtivacao = $statusAnterior !== AssinaturaUsuario::ST_ACTIVE;

        $assinatura->status = AssinaturaUsuario::ST_ACTIVE;
        $assinatura->save();

        LogService::info('Pagamento confirmado via webhook', [
            'assinatura_id' => $assinatura->id,
            'user_id' => $assinatura->user_id,
            'payment_id' => $payment['id'],
            'status_anterior' => $statusAnterior,
            'status_novo' => AssinaturaUsuario::ST_ACTIVE,
            'payment_status' => $payment['status'] ?? null,
            'payment_date' => $payment['paymentDate'] ?? null,
        ]);

        // 📧 Enviar email de confirmação de pagamento
        if ($isPrimeiraAtivacao) {
            $this->sendPaymentConfirmationEmail($assinatura, $payment);
        }
    }

    /**
     * Envia email de confirmação de pagamento/assinatura ativada
     */
    /**
     * @param array<string, mixed> $payment
     */
    private function sendPaymentConfirmationEmail(AssinaturaUsuario $assinatura, array $payment): void
    {
        try {
            $usuario = Usuario::find($assinatura->user_id);

            if (!$usuario || empty($usuario->email)) {
                LogService::persist(
                    LogLevel::WARNING,
                    LogCategory::NOTIFICATION,
                    'Usuário não encontrado para enviar email de confirmação',
                    ['user_id' => $assinatura->user_id],
                );
                return;
            }

            // Buscar nome do plano
            $planoNome = 'PRO';
            if ($assinatura->plano_id) {
                $plano = \Application\Models\Plano::find($assinatura->plano_id);
                if ($plano) {
                    $planoNome = $plano->nome ?? 'PRO';
                }
            }

            // Obter valor do pagamento
            $valor = isset($payment['value']) ? (float) $payment['value'] : null;
            $renovaEm = $assinatura->renova_em?->format('Y-m-d H:i:s');

            $this->mailService->sendSubscriptionConfirmation(
                $usuario->email,
                $usuario->nome ?? 'Usuário',
                $planoNome,
                $renovaEm,
                $valor
            );

            LogService::info('Email de confirmação de pagamento enviado', [
                'user_id' => $usuario->id,
                'email' => $usuario->email,
                'plano' => $planoNome,
            ]);
        } catch (\Throwable $e) {
            // Não falhar o webhook por erro no email
            LogService::captureException($e, LogCategory::NOTIFICATION, [
                'action' => 'send_payment_confirmation_email',
                'user_id' => $assinatura->user_id,
            ], $assinatura->user_id);
        }
    }

    private function mapSubscriptionStatus(?string $status): ?string
    {
        return match ($status) {
            'ACTIVE' => AssinaturaUsuario::ST_ACTIVE,
            'EXPIRED', 'SUSPENDED' => AssinaturaUsuario::ST_PAST_DUE,
            'CANCELED' => AssinaturaUsuario::ST_CANCELED,
            default => null,
        };
    }

    /**
     * @return array<string, mixed>
     */
    protected function readRequestHeaders(): array
    {
        return $this->request->headers();
    }

    protected function readRawBody(): string
    {
        return $this->request->rawInput();
    }

    private function requestIp(): string
    {
        return $this->request->ip();
    }

    private function plainTextResponse(string $content, int $statusCode = 200): Response
    {
        return Response::htmlResponse($content, $statusCode)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function buildWebhookPayloadSummary(array $payload, string $rawBody): array
    {
        $payment = is_array($payload['payment'] ?? null) ? $payload['payment'] : [];
        $subscription = is_array($payload['subscription'] ?? null) ? $payload['subscription'] : [];

        return [
            'id' => $payload['id'] ?? null,
            'event' => $payload['event'] ?? null,
            'payment_id' => $payment['id'] ?? null,
            'subscription_id' => $subscription['id'] ?? $payment['subscription'] ?? null,
            'customer_id' => $payment['customer'] ?? $subscription['customer'] ?? null,
            'status' => $payment['status'] ?? $subscription['status'] ?? null,
            'billing_type' => $payment['billingType'] ?? $subscription['billingType'] ?? null,
            'value' => $payment['value'] ?? $subscription['value'] ?? null,
            'payload_hash' => hash('sha256', $rawBody),
        ];
    }
}
