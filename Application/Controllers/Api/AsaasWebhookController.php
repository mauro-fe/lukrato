<?php

namespace Application\Controllers\Api;

use Application\Controllers\BaseController;
use Application\Models\AssinaturaUsuario;
use Application\Models\LogWebhookCobranca;
use Application\Services\AsaasService;
use Application\Services\LogService;

class AsaasWebhookController extends BaseController
{
    private AsaasService $asaas;

    public function __construct()
    {
        parent::__construct();
        $this->asaas = new AsaasService();
    }

    public function receive(): void
    {
        $headers = function_exists('getallheaders') ? getallheaders() : [];

        if (!$this->asaas->validateWebhookRequest($headers)) {
            if (class_exists(LogService::class)) {
                LogService::warning('Webhook Asaas com token inválido', ['headers' => $headers]);
            }
            http_response_code(401);
            echo 'Invalid token';
            return;
        }

        $rawBody = file_get_contents('php://input');
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

        // Loga TODO webhook na tabela
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
            if (str_starts_with($event, 'SUBSCRIPTION_')) {
                $this->handleSubscriptionEvent($event, $payload);
            } elseif (str_starts_with($event, 'PAYMENT_')) {
                $this->handlePaymentEvent($event, $payload);
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

        $assinatura = $query->latest('id')->first();

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

        // Exemplo simples: quando pagamento RECEIVED, marca assinatura como ativa
        if ($subscriptionId && $status === 'RECEIVED') {
            $assinatura = AssinaturaUsuario::where('external_subscription_id', $subscriptionId)
                ->latest('id')
                ->first();

            if ($assinatura) {
                $assinatura->status = AssinaturaUsuario::ST_ACTIVE;
                // se quiser, pode criar um campo last_payment_at na tabela futuramente
                $assinatura->save();
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
