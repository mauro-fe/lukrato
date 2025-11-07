<?php

namespace Application\Controllers\Api;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Services\MercadoPagoService;
use Application\Services\LogService;
use Application\Lib\Auth;
use Exception;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\Exceptions\MPApiException;

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
            $user = Auth::user();
            if (!$user) {
                Response::unauthorized('Usuário não autenticado');
                return;
            }

            $json   = json_decode(file_get_contents('php://input'), true) ?? [];
            $amount = (float)($json['amount'] ?? 0);
            $title  = (string)($json['title'] ?? 'Assinatura Pro Lukrato');
            $data   = (array)($json['data'] ?? []); // payload do Brick

            // --- campos que o Brick manda em camelCase ---
            $token           = (string)($data['token'] ?? '');
            $paymentMethodId = $data['payment_method_id'] ?? $data['paymentMethodId'] ?? null;
            $issuerId        = $data['issuer_id'] ?? $data['issuerId'] ?? null;
            $installments    = (int)($data['installments'] ?? 1);

            // documento (CPF/CNPJ) – sanitiza
            $idType   = $data['payer']['identification']['type']
                ?? $data['identificationType'] ?? 'CPF';
            $idNumber = $data['payer']['identification']['number']
                ?? $data['identificationNumber'] ?? '';
            $idNumber = preg_replace('/\D+/', '', (string)$idNumber);

            if ($amount <= 0 || empty($token) || empty($paymentMethodId)) {
                Response::validationError([
                    'amount/token/paymentMethodId' => 'Dados obrigatórios ausentes',
                ]);
                return;
            }

            MercadoPagoService::configureSdk();

            $notificationUrl = rtrim($_ENV['MP_CALLBACK_BASE'] ?? BASE_URL, '/') . '/api/webhooks/mercadopago';
            $externalRef     = 'user_' . $user->id . '_lukrato_' . uniqid();

            $payload = [
                'transaction_amount' => round($amount, 2),
                'token'              => $token,
                'description'        => $title,
                'installments'       => max(1, $installments),
                'payment_method_id'  => (string)$paymentMethodId,
                'capture'            => true,
                'payer' => [
                    'email' => $data['payer']['email'] ?? $user->email,
                    'first_name' => $user->nome ?? $user->username,
                    'identification' => [
                        'type'   => $idType,
                        'number' => $idNumber,
                    ],
                ],
                'metadata' => [
                    'user_id'  => (int)$user->id,
                    'username' => (string)$user->username,
                    'origin'   => 'lukrato_bricks',
                ],
                'external_reference' => $externalRef,
                'notification_url'   => $notificationUrl,
            ];
            if (!empty($issuerId)) {
                $payload['issuer_id'] = (int)$issuerId;
            }

            LogService::info('MP pagamento: preparando payload', [
                'amount'             => $payload['transaction_amount'],
                'installments'       => $payload['installments'],
                'payment_method_id'  => $payload['payment_method_id'],
                'issuer_id'          => $payload['issuer_id'] ?? null,
                'external_reference' => $payload['external_reference'],
                'has_token'          => !empty($payload['token']),
            ]);

            $client  = new PaymentClient();
            $payment = $client->create($payload);

            // Não ativa aqui — webhook fará o upgrade quando status=approved
            Response::success([
                'payment_id'    => $payment->id,
                'status'        => $payment->status,
                'status_detail' => $payment->status_detail ?? null,
            ]);
        } catch (MPApiException $e) {
            $apiResponse = $e->getApiResponse();
            $statusCode  = $apiResponse ? $apiResponse->getStatusCode() : 400;
            $raw         = $apiResponse ? $apiResponse->getContent() : null;
            $body        = is_string($raw) ? json_decode($raw, true) : (is_array($raw) ? $raw : null);

            LogService::error('MPApiException em pay()', [
                'http_status' => $statusCode,
                'message'     => $e->getMessage(),
                'body'        => $body,
            ]);

            // mensagem amigável
            $human = $body['message'] ?? ($body['error'] ?? 'Pagamento recusado pelo Mercado Pago');
            Response::error($human, 400, ['origin' => 'mercadopago', 'detail' => $body['cause'] ?? $body]);
        } catch (\Throwable $e) {
            LogService::error('Erro pay()', ['ex' => $e->getMessage()]);
            Response::error('Pagamento recusado/erro', 400);
        }
    }
}
