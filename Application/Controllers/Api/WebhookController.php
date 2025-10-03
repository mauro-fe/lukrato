<?php

namespace Application\Controllers\Api;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Models\Usuario;
use Application\Models\LogWebhookCobranca;
use Application\Services\PagarmeService;

class WebhookController extends BaseController
{
    // POST /api/webhooks/pagarme
    public function pagarme(): void
    {
        $raw = file_get_contents('php://input') ?: '';
        $headers = function_exists('getallheaders') ? getallheaders() : [];

        $service = new PagarmeService();

        // (Recomendado) validar assinatura/HMAC do webhook
        if (!$service->isValidWebhook($headers, $raw)) {
            Response::forbidden('Assinatura do webhook inválida'); // 403
            return;
        }

        $payload = json_decode($raw, true) ?? [];
        $event   = $payload['type'] ?? ($payload['event'] ?? ''); // conforme versão da API
        $data    = $payload['data'] ?? ($payload['object'] ?? []);

        // Log do webhook para auditoria/depuração
        try {
            LogWebhookCobranca::create([
                'provedor'    => 'pagarme',
                'tipo_evento' => (string)$event,
                'payload'     => $payload,
            ]);
        } catch (\Throwable $e) {
            // Se o log falhar, não bloqueia o processamento do benefício
        }

        try {
            switch ($event) {
                // pagamento OK → liberar “sem anúncios”
                case 'invoice.paid':
                case 'subscription.paid': {
                        $customerId     = $data['customer_id'] ?? ($data['customer']['id'] ?? null);
                        $subscriptionId = $data['subscription_id'] ?? ($data['subscription']['id'] ?? null);
                        $periodEnd      = $data['period_end'] ?? null;

                        if ($customerId) {
                            $user = Usuario::where('pagarme_cliente_id', $customerId)->first();
                            if ($user) {
                                $user->plano = 'sem-anuncios';
                                $user->anuncios_desativados = 1;
                                if ($subscriptionId) $user->pagarme_assinatura_id = $subscriptionId;
                                if ($periodEnd) {
                                    $timestamp = is_numeric($periodEnd) ? (int)$periodEnd : strtotime($periodEnd);
                                    if ($timestamp) $user->plano_renova_em = date('Y-m-d H:i:s', $timestamp);
                                }
                                $user->save();
                            }
                        }
                        break;
                    }

                    // falha/cancelamento/pausa → voltar ao “gratuito”
                case 'invoice.payment_failed':
                case 'subscription.canceled':
                case 'subscription.paused': {
                        $customerId = $data['customer_id'] ?? ($data['customer']['id'] ?? null);
                        if ($customerId) {
                            $user = Usuario::where('pagarme_cliente_id', $customerId)->first();
                            if ($user) {
                                $user->plano = 'gratuito';
                                $user->anuncios_desativados = 0;
                                $user->plano_renova_em = null;
                                $user->save();
                            }
                        }
                        break;
                    }
            }

            Response::success(['received' => true]); // 200
        } catch (\Throwable $e) {
            Response::error('Erro ao processar webhook: ' . $e->getMessage(), 500);
        }
    }
}
