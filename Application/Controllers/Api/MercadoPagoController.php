<?php

namespace Application\Controllers\Api;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Services\MercadoPagoService;
use Application\Services\LogService;
use Application\Lib\Auth;
use Exception;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Payment\PaymentClient;

class MercadoPagoController extends BaseController
{
    public function createCheckout(): void
    {
        $this->requireAuthApi();

        try {
            $user = Auth::user();
            if (!$user) {
                Response::unauthorized('Usuário não autenticado');
                return; // opcional (Response já dá exit)
            }

            $mp   = new MercadoPagoService();
            $pref = $mp->createCheckoutPreference([
                'user_id'  => $user->id,
                'username' => $user->username,
                'email'    => $user->email,
                'name'     => $user->nome ?? $user->username,
                'amount'   => 12.00,
                'title'    => 'Assinatura Pro Lukrato',
            ]);

            // Prioriza init_point; fallback para sandbox_init_point
            $checkoutUrl = $pref['init_point'] ?? ($pref['sandbox_init_point'] ?? null);
            if (!$checkoutUrl) {
                Response::error('Checkout URL não retornada pela API do Mercado Pago', 500);
                return;
            }

            // Estética: remover “sandbox.” se a flag estiver ativa (apenas dev)
            if (!empty($_ENV['MP_FORCE_NO_SANDBOX_IN_URL']) && strpos($checkoutUrl, 'sandbox.') !== false) {
                $checkoutUrl = str_replace('sandbox.', '', $checkoutUrl);
            }

            LogService::info('Checkout URL escolhido', [
                'checkoutUrl' => $checkoutUrl,
                'pref_id'     => $pref['id'] ?? null,
            ]);

            Response::success([
                'preference_id'      => $pref['id'],
                'init_point'         => $checkoutUrl,
                'raw_init_point'     => $pref['init_point'] ?? null,
                'sandbox_init_point' => $pref['sandbox_init_point'] ?? null,
            ]);
        } catch (Exception $e) {
            Response::error('Falha ao criar checkout: ' . $e->getMessage(), 500);
        }
    }
    public function pay(): void
{
    $this->requireAuthApi();

    try {
        $user = \Application\Lib\Auth::user();
        if (!$user) { Response::unauthorized('Usuário não autenticado'); return; }

        $json   = json_decode(file_get_contents('php://input'), true) ?? [];
        $amount = (float)($json['amount'] ?? 0);
        $title  = (string)($json['title'] ?? 'Assinatura Pro Lukrato');
        $data   = (array)($json['data'] ?? []); // vem do Brick

        if ($amount <= 0 || empty($data['token'])) {
            Response::validationError(['amount/token' => 'Dados inválidos']);
            return;
        }

        MercadoPagoConfig::setAccessToken($_ENV['MP_ACCESS_TOKEN']);

        $notificationUrl = rtrim($_ENV['MP_CALLBACK_BASE'] ?? BASE_URL, '/') . '/api/webhooks/mercadopago';
        $externalRef     = 'user_' . $user->id . '_lukrato_' . uniqid();

        $payload = [
            'transaction_amount' => $amount,
            'token'              => $data['token'],
            'description'        => $title,
            'installments'       => (int)($data['installments'] ?? 1),
            'payment_method_id'  => $data['paymentMethodId'] ?? null,   // ex.: visa
            'issuer_id'          => $data['issuerId'] ?? null,
            'capture'            => true,
            'payer' => [
                'email' => $data['payer']['email'] ?? $user->email,
                'identification' => [
                    'type'   => $data['payer']['identification']['type']   ?? 'CPF',
                    'number' => $data['payer']['identification']['number'] ?? '',
                ],
            ],
            'metadata' => [
                'user_id'  => $user->id,
                'username' => $user->username,
                'origin'   => 'lukrato_bricks',
            ],
            'external_reference' => $externalRef,
            'notification_url'   => $notificationUrl,
        ];

        $client  = new PaymentClient();
        $payment = $client->create($payload);

        // Não ative o plano aqui; o webhook vai confirmar (status=approved)
        Response::success([
            'payment_id' => $payment->id,
            'status'     => $payment->status, // approved/pending/rejected
        ]);

    } catch (\Throwable $e) {
        LogService::error('Erro pay()', ['ex' => $e->getMessage()]);
        Response::error('Pagamento recusado/erro', 400);
    }
}
}
