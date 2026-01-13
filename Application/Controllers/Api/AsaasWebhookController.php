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

    public function test(): void
    {
        echo 'Webhook OK (GET)';
        exit;
    }


    public function receive(): void
    {
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $rawBody = file_get_contents('php://input');

        // ✅ Validação com múltiplas camadas de segurança
        if (!$this->asaas->validateWebhookRequest($headers, $rawBody)) {
            if (class_exists(LogService::class)) {
                LogService::warning('Webhook Asaas com validação falhou', [
                    'headers' => array_keys($headers),
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                ]);
            }
            http_response_code(401);
            echo 'Unauthorized';
            return;
        }

        $payload = json_decode($rawBody, true);

        if (!is_array($payload)) {
            if (class_exists(LogService::class)) {
                LogService::warning('Webhook Asaas com payload inválido', ['rawBody' => $rawBody]);
            }
            http_response_code(400);
            echo 'Invalid payload';
            return;
        }

        $event = $payload['event'] ?? null;

        // ✅ IDEMPOTÊNCIA - Criar chave única baseada em event + ID + timestamp
        $eventId = $payload['id'] ?? null;
        $idempotencyKey = md5($event . '_' . $eventId . '_' . ($payload['payment']['id'] ?? $payload['subscription']['id'] ?? ''));

        // ✅ Verificar se já processamos este webhook
        $jaProcessado = DB::table('webhook_idempotencia')
            ->where('idempotency_key', $idempotencyKey)
            ->exists();

        if ($jaProcessado) {
            if (class_exists(LogService::class)) {
                LogService::info('Webhook já processado (idempotência)', [
                    'idempotency_key' => $idempotencyKey,
                    'event' => $event,
                ]);
            }
            http_response_code(200);
            echo 'Already processed';
            return;
        }

        LogWebhookCobranca::create([
            'provedor'   => 'asaas',
            'tipo_evento' => $event ?? 'unknown',
            'payload'    => $payload,
        ]);

        if (!$event) {
            http_response_code(200);
            echo 'OK';
            return;
        }

        try {
            // ✅ TRANSAÇÃO para garantir atomicidade
            DB::beginTransaction();

            try {
                // ✅ Registrar na tabela de idempotência ANTES de processar
                DB::table('webhook_idempotencia')->insert([
                    'idempotency_key' => $idempotencyKey,
                    'event_type' => $event,
                    'payload_hash' => hash('sha256', $rawBody),
                    'processed_at' => now(),
                    'created_at' => now(),
                ]);

                if (str_starts_with($event, 'SUBSCRIPTION_')) {
                    $this->handleSubscriptionEvent($event, $payload);
                } elseif (str_starts_with($event, 'PAYMENT_')) {
                    $this->handlePaymentEvent($event, $payload);
                }

                DB::commit();
            } catch (\Throwable $txError) {
                DB::rollBack();
                throw $txError;
            }
        } catch (\Throwable $e) {
            if (class_exists(LogService::class)) {
                LogService::error('Erro ao processar webhook Asaas', [
                    'event'   => $event,
                    'error'   => $e->getMessage(),
                    'trace'   => $e->getTraceAsString(),
                ]);
            }
        }

        http_response_code(200);
        echo 'OK';
    }

    private function handleSubscriptionEvent(string $event, array $payload): void
    {
        $subscriptionData = $payload['subscription'] ?? null;
        if (!$subscriptionData || empty($subscriptionData['id'])) {
            return;
        }

        $asaasId     = $subscriptionData['id'];
        $externalRef = $subscriptionData['externalReference'] ?? null;
        $asaasStatus = $subscriptionData['status'] ?? null;
        $nextDueDate = $subscriptionData['nextDueDate'] ?? null;

        $interno = $this->mapSubscriptionStatus($asaasStatus);

        $query = AssinaturaUsuario::where('external_subscription_id', $asaasId);

        if ($externalRef) {
            $query->orWhere('external_customer_id', $externalRef);
        }

        // ✅ LOCK PESSIMISTA para evitar race condition
        $assinatura = $query->lockForUpdate()->latest('id')->first();

        if (!$assinatura) {
            if (class_exists(LogService::class)) {
                LogService::warning('Assinatura Asaas não encontrada localmente', [
                    'asaasId'     => $asaasId,
                    'externalRef' => $externalRef,
                    'status'      => $asaasStatus,
                ]);
            }
            return;
        }

        if ($interno) {
            $assinatura->status = $interno;
        }

        if ($nextDueDate) {
            $assinatura->renova_em = $nextDueDate;
        }

        $assinatura->save();

        // ✅ Log de auditoria
        if (class_exists(LogService::class)) {
            LogService::info('Assinatura atualizada via webhook', [
                'assinatura_id' => $assinatura->id,
                'user_id' => $assinatura->user_id,
                'status_anterior' => $assinatura->getOriginal('status'),
                'status_novo' => $interno,
                'event' => $event,
            ]);
        }
    }

    private function handlePaymentEvent(string $event, array $payload): void
    {
        $payment = $payload['payment'] ?? null;
        if (!$payment || empty($payment['id'])) {
            return;
        }

        $subscriptionId = $payment['subscription'] ?? null;
        $status         = $payment['status'] ?? null;
        $paymentDate    = $payment['paymentDate'] ?? null;

        if ($subscriptionId && $status === 'RECEIVED') {
            // ✅ LOCK para evitar dupla atualização
            $assinatura = AssinaturaUsuario::where('external_subscription_id', $subscriptionId)
                ->lockForUpdate()
                ->latest('id')
                ->first();

            if ($assinatura) {
                $statusAnterior = $assinatura->status;
                $assinatura->status = AssinaturaUsuario::ST_ACTIVE;
                $assinatura->save();

                // ✅ Log de auditoria
                if (class_exists(LogService::class)) {
                    LogService::info('Pagamento confirmado via webhook', [
                        'assinatura_id' => $assinatura->id,
                        'user_id' => $assinatura->user_id,
                        'payment_id' => $payment['id'],
                        'status_anterior' => $statusAnterior,
                        'status_novo' => AssinaturaUsuario::ST_ACTIVE,
                        'payment_date' => $paymentDate,
                    ]);
                }
            }
        }
    }

    private function mapSubscriptionStatus(?string $asaasStatus): ?string
    {
        return match ($asaasStatus) {
            'ACTIVE'   => AssinaturaUsuario::ST_ACTIVE,
            'EXPIRED',
            'SUSPENDED' => AssinaturaUsuario::ST_PAST_DUE,
            'CANCELED' => AssinaturaUsuario::ST_CANCELED,
            default    => null,
        };
    }
}
