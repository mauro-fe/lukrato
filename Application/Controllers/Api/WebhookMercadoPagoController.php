<?php

namespace Application\Controllers\Api;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Models\Usuario;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Payment\PaymentClient;

class WebhookMercadoPagoController extends BaseController
{
    public function handle(): void
    {
        MercadoPagoConfig::setAccessToken($_ENV['MP_ACCESS_TOKEN'] ?? '');

        $payload = json_decode(file_get_contents('php://input'), true) ?? [];
        $type    = $payload['type']  ?? $payload['action'] ?? '';
        $id      = $payload['data']['id'] ?? null;

        try {
            if ($type === 'payment' && $id) {
                $client  = new PaymentClient();
                $payment = $client->get($id); // consulta status

                if ($payment && $payment->status === 'approved') {
                    $userId = (int)($payment->metadata->user_id ?? 0);
                    $user   = $userId ? Usuario::find($userId) : null;

                    if ($user) {
                        $user->plano = 'pro';
                        $user->plano_renova_em = date('Y-m-d H:i:s', strtotime('+30 days'));
                        $user->save();
                    }
                }
            }
            Response::success(['received' => true]);
        } catch (\Throwable $e) {
            Response::error('Erro no webhook: ' . $e->getMessage(), 500);
        }
    }
}
