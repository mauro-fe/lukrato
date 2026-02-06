<?php

namespace Application\Controllers\Api;

use Application\Controllers\BaseController;
use Application\Models\AssinaturaUsuario;
use Application\Models\LogWebhookCobranca;
use Application\Services\AsaasService;
use Application\Services\LogService;
use Illuminate\Database\Capsule\Manager as DB;

class AsaasWebhookController extends BaseController
{
    private AsaasService $asaas;

    public function __construct()
    {
        parent::__construct();
        $this->asaas = new AsaasService();
    }

    /**
     * Endpoint de teste - APENAS para desenvolvimento
     * Em produção retorna 404 para não expor informações
     */
    public function test(): void
    {
        // Em produção, não revelar que o endpoint existe
        if (defined('APP_ENV') && APP_ENV === 'production') {
            http_response_code(404);
            echo 'Not Found';
            exit;
        }

        // Em desenvolvimento, permitir verificar se está funcionando
        echo 'Webhook OK (dev only)';
        exit;
    }

    public function receive(): void
    {
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $rawBody = file_get_contents('php://input');

        // 🔐 Validação de segurança (NUNCA retornar erro externo)
        if (!$this->asaas->validateWebhookRequest($headers, $rawBody)) {
            LogService::warning('Webhook Asaas rejeitado por validação', [
                'headers' => array_keys($headers),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            ]);

            http_response_code(200);
            echo 'OK';
            return;
        }

        $payload = json_decode($rawBody, true);

        if (!is_array($payload)) {
            LogService::warning('Webhook Asaas com payload inválido', [
                'rawBody' => $rawBody,
            ]);

            http_response_code(200);
            echo 'OK';
            return;
        }

        $event = $payload['event'] ?? null;

        if (!$event) {
            http_response_code(200);
            echo 'OK';
            return;
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

            http_response_code(200);
            echo 'OK';
            return;
        }

        // 📦 Log bruto
        LogWebhookCobranca::create([
            'provedor' => 'asaas',
            'tipo_evento' => $event,
            'payload' => $payload,
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

            LogService::error('Erro ao processar webhook Asaas', [
                'event' => $event,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        http_response_code(200);
        echo 'OK';
    }

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
            LogService::warning('Assinatura não encontrada para webhook', [
                'asaas_id' => $asaasId,
            ]);
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

    private function handlePaymentEvent(string $event, array $payload): void
    {
        $payment = $payload['payment'] ?? null;

        if (!$payment || empty($payment['id'])) {
            return;
        }

        // ⚠️ ATIVAR PLANO APENAS SE FOR PAGAMENTO DE ASSINATURA
        if (
            empty($payment['subscription']) ||
            ($payment['status'] ?? null) !== 'RECEIVED'
        ) {
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
            return;
        }

        $statusAnterior = $assinatura->status;

        $assinatura->status = AssinaturaUsuario::ST_ACTIVE;
        $assinatura->save();

        LogService::info('Pagamento confirmado via webhook', [
            'assinatura_id' => $assinatura->id,
            'user_id' => $assinatura->user_id,
            'payment_id' => $payment['id'],
            'status_anterior' => $statusAnterior,
            'status_novo' => AssinaturaUsuario::ST_ACTIVE,
            'payment_date' => $payment['paymentDate'] ?? null,
        ]);
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
}
