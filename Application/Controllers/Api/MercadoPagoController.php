<?php

namespace Application\Controllers\Api;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Services\MercadoPagoService;
use Application\Services\LogService;
use Application\Lib\Auth;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\Exceptions\MPApiException;
use Throwable; // Importa Throwable para capturar Exceptions e Errors

class MercadoPagoController extends BaseController
{
    /**
     * Cria uma Preferência de Checkout (link de pagamento).
     */
    public function createCheckout(): void
    {
        $this->requireAuthApi();

        try {
            /** @var \Application\Models\Usuario|null $user */
            $user = Auth::user();
            if (!$user) {
                Response::unauthorized('Usuário não autenticado');
                return;
            }

            $mp   = new MercadoPagoService();
            $pref = $mp->createCheckoutPreference([
                'user_id'  => (int)$user->id,
                'username' => (string)$user->username,
                'email'    => (string)$user->email,
                'name'     => (string)($user->nome ?? $user->username),
                'amount'   => 12.00, // Valor fixo
                'title'    => 'Assinatura Pro Lukrato',
            ]);

            $checkoutUrl = $pref['init_point'] ?? $pref['sandbox_init_point'] ?? null;
            if ($checkoutUrl === null) {
                Response::error('Checkout URL não retornada pela API do Mercado Pago', 500);
                return;
            }

            if (!empty($_ENV['MP_FORCE_NO_SANDBOX_IN_URL']) && str_contains($checkoutUrl, 'sandbox.')) {
                $checkoutUrl = str_replace('sandbox.', '', $checkoutUrl);
            }

            LogService::info('Checkout URL escolhido', [
                'checkoutUrl' => $checkoutUrl,
                'pref_id'     => $pref['id'] ?? null,
            ]);

            Response::success([
                'preference_id'      => $pref['id'] ?? null,
                'init_point'         => $checkoutUrl,
                'raw_init_point'     => $pref['init_point'] ?? null,
                'sandbox_init_point' => $pref['sandbox_init_point'] ?? null,
            ]);
        } catch (Throwable $e) {
            Response::error('Falha ao criar checkout: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Processa um pagamento via Brick (cartão de crédito).
     */
    public function pay(): void
    {
        $this->requireAuthApi();

        try {
            /** @var \Application\Models\Usuario|null $user */
            $user = Auth::user();
            if (!$user) {
                Response::unauthorized('Usuário não autenticado');
                return;
            }
            $userId = (int)$user->id;

            $json   = json_decode(file_get_contents('php://input'), true) ?? [];
            $amount = (float)($json['amount'] ?? 0);
            $title  = (string)($json['title'] ?? 'Assinatura Pro Lukrato');
            $data   = (array)($json['data'] ?? []); // payload do Brick

            // Coalescência de chaves (camelCase/snake_case)
            $token           = (string)($data['token'] ?? '');
            $paymentMethodId = (string)($data['payment_method_id'] ?? $data['paymentMethodId'] ?? '');
            $issuerId        = $data['issuer_id'] ?? $data['issuerId'] ?? null;
            $installments    = (int)($data['installments'] ?? 1);

            // Documento (CPF/CNPJ) – uso de Nullsafe e sanitização
            $idType   = $data['payer']['identification']['type'] 
                        ?? $data['identificationType'] ?? 'CPF';
            $idNumber = $data['payer']['identification']['number'] 
                        ?? $data['identificationNumber'] ?? '';
            $idNumber = preg_replace('/\D+/', '', (string)$idNumber);

            if ($amount <= 0 || $token === '' || $paymentMethodId === '') {
                Response::validationError([
                    'payment_data' => 'Dados obrigatórios (valor, token, método de pagamento) ausentes.',
                ]);
                return;
            }

            MercadoPagoService::configureSdk();

            $notificationUrl = rtrim($_ENV['MP_CALLBACK_BASE'] ?? BASE_URL, '/') . '/api/webhooks/mercadopago';
            $externalRef     = 'user_' . $userId . '_lukrato_' . uniqid();

            $payload = [
                'transaction_amount' => round($amount, 2),
                'token'              => $token,
                'description'        => $title,
                'installments'       => max(1, $installments),
                'payment_method_id'  => $paymentMethodId,
                'capture'            => true,
                'payer' => [
                    'email'          => (string)($data['payer']['email'] ?? $user->email),
                    'first_name'     => (string)($user->nome ?? $user->username),
                    'identification' => [
                        'type'   => (string)$idType,
                        'number' => (string)$idNumber,
                    ],
                ],
                'metadata' => [
                    'user_id'  => $userId,
                    'username' => (string)$user->username,
                    'origin'   => 'lukrato_bricks',
                ],
                'external_reference' => $externalRef,
                'notification_url'   => $notificationUrl,
            ];
            
            if ($issuerId !== null) {
                $payload['issuer_id'] = (int)$issuerId;
            }

            LogService::info('MP pagamento: preparando payload', [
                'amount' => $payload['transaction_amount'],
                'external_reference' => $payload['external_reference'],
            ]);

            $client  = new PaymentClient();
            $payment = $client->create($payload);

            Response::success([
                'payment_id'    => (int)$payment->id,
                'status'        => (string)$payment->status,
                'status_detail' => (string)($payment->status_detail ?? null),
            ]);
        } catch (MPApiException $e) {
            $apiResponse = $e->getApiResponse();
            // Uso do operador Nullsafe (PHP 8.0) para acesso seguro
            $statusCode  = $apiResponse?->getStatusCode() ?? 400;
            $raw         = $apiResponse?->getContent();
            $body        = is_string($raw) ? json_decode($raw, true) : (is_array($raw) ? $raw : null);

            LogService::error('MPApiException em pay()', [
                'http_status' => $statusCode,
                'message'     => $e->getMessage(),
                'body'        => $body,
            ]);

            $human = $body['message'] ?? $body['error'] ?? 'Pagamento recusado pelo Mercado Pago';
            Response::error($human, 400, ['origin' => 'mercadopago', 'detail' => $body['cause'] ?? $body]);
        } catch (Throwable $e) {
            LogService::error('Erro pay()', ['ex' => $e->getMessage()]);
            Response::error('Erro interno no processamento do pagamento', 500);
        }
    }
}