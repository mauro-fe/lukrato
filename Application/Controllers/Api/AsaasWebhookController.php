<?php

namespace Application\Controllers\Api;

use Application\Controllers\BaseController;
use Application\Models\AssinaturaUsuario;
use Application\Models\LogWebhookCobranca;
use Application\Models\Usuario;
use Application\Services\AsaasService;
use Application\Services\LogService;
use Application\Services\MailService;
use Illuminate\Database\Capsule\Manager as DB;

class AsaasWebhookController extends BaseController
{
    private AsaasService $asaas;

    public function __construct()
    {
        parent::__construct();
        $this->asaas = new AsaasService();
    }

    public function test(): void
    {
        echo 'Webhook OK';
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
        $isPrimeiraAtivacao = $statusAnterior !== AssinaturaUsuario::ST_ACTIVE;

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

        // 📧 Enviar email de confirmação de pagamento
        if ($isPrimeiraAtivacao) {
            $this->sendPaymentConfirmationEmail($assinatura, $payment);
        }
    }

    /**
     * Envia email de confirmação de pagamento/assinatura ativada
     */
    private function sendPaymentConfirmationEmail(AssinaturaUsuario $assinatura, array $payment): void
    {
        try {
            $usuario = Usuario::find($assinatura->user_id);
            
            if (!$usuario || empty($usuario->email)) {
                LogService::warning('Usuário não encontrado para enviar email de confirmação', [
                    'user_id' => $assinatura->user_id,
                ]);
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

            $mailService = new MailService();
            $mailService->sendSubscriptionConfirmation(
                $usuario->email,
                $usuario->nome ?? 'Usuário',
                $planoNome,
                $assinatura->renova_em,
                $valor
            );

            LogService::info('Email de confirmação de pagamento enviado', [
                'user_id' => $usuario->id,
                'email' => $usuario->email,
                'plano' => $planoNome,
            ]);
        } catch (\Throwable $e) {
            // Não falhar o webhook por erro no email
            LogService::error('Erro ao enviar email de confirmação de pagamento', [
                'user_id' => $assinatura->user_id,
                'error' => $e->getMessage(),
            ]);
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
}
