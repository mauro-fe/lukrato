<?php
// Application/Services/MercadoPagoService.php
namespace Application\Services;

use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Client\Common\RequestOptions;
use MercadoPago\Exceptions\MPApiException;

final class MercadoPagoService
{
    public function __construct()
    {
        $token = $_ENV['MP_ACCESS_TOKEN'] ?? '';
        if (!$token) {
            LogService::error('MP_ACCESS_TOKEN ausente no .env');
            throw new \RuntimeException('Credenciais do Mercado Pago ausentes.');
        }
        MercadoPagoConfig::setAccessToken($token);
        LogService::info('MercadoPagoConfig token setado (ok)');
    }

    /** base para callbacks; prefere MP_CALLBACK_BASE se existir */
    private function base(string $path = ''): string
    {
        $raw = $_ENV['MP_CALLBACK_BASE'] ?? (defined('BASE_URL') ? BASE_URL : '/');
        $base = rtrim($raw, '/') . '/';
        return $base . ltrim($path, '/');
    }

    private function isHttps(string $url): bool
    {
        return stripos($url, 'https://') === 0;
    }

    public function createCheckoutPreference(array $data): array
    {
        // --- validações mínimas ---
        $amount  = (float)($data['amount'] ?? 0);
        $email   = (string)($data['email'] ?? '');
        $userId  = (int)($data['user_id'] ?? 0);
        $title   = (string)($data['title'] ?? 'Assinatura Lukrato Pro');
        $name    = (string)($data['name'] ?? '');

        if ($amount <= 0 || !$email || $userId <= 0) {
            LogService::error('Preference inválida: amount/email/user_id', [
                'amount' => $amount,
                'email' => $email,
                'user_id' => $userId
            ]);
            throw new \InvalidArgumentException('Dados inválidos para criar preferência.');
        }

        $client = new PreferenceClient();

        $success = $this->base('billing?status=success');
        $pending = $this->base('billing?status=pending');
        $failure = $this->base('billing?status=failure');
        $notify  = $this->base('api/webhooks/mercadopago');

        $externalRef = 'user_' . $userId . '_lukrato_' . uniqid();

        $env = strtolower(trim($_ENV['MP_ENV'] ?? 'production'));
        $isSandbox = ($env === 'sandbox');

        if ($isSandbox) {
            $testBuyer = $_ENV['MP_TEST_BUYER_EMAIL'] ?? '';
            if ($testBuyer && stripos($email, 'test_user') === false) {
                LogService::info('Sandbox: forçando comprador de teste', [
                    'from' => $email,
                    'to' => $testBuyer
                ]);
                $email = $testBuyer;
            }
        }

        $payload = [
            'items' => [[
                'title'       => $title,
                'quantity'    => 1,
                'currency_id' => 'BRL',
                'unit_price'  => $amount,
            ]],
            'payer' => [
                'email' => $email,
                'name'  => $name,
            ],
            'back_urls' => [
                'success' => $success,
                'pending' => $pending,
                'failure' => $failure,
            ],
            'notification_url'   => $notify,
            'external_reference' => $externalRef,
            'metadata' => [
                'user_id'  => $userId,
                'username' => (string)($data['username'] ?? ''),
                'origin'   => 'lukrato_billing',
            ],

            // (Opcional) Controle de métodos de pagamento:
            // 'payment_methods' => [
            //     'excluded_payment_types' => [ ['id' => 'ticket'] ], // exclui boleto
            //     'installments' => 1
            // ],
        ];

        // Só inclui auto_return se as URLs forem HTTPS
        if ($this->isHttps($success) && $this->isHttps($pending) && $this->isHttps($failure)) {
            $payload['auto_return'] = 'approved';
        } else {
            LogService::info('auto_return omitido (URLs não-HTTPS em dev)', [
                'success' => $success,
                'pending' => $pending,
                'failure' => $failure
            ]);
        }

        try {
            LogService::info('MP Preference create: payload', [
                'items' => $payload['items'],
                'payer' => ['email' => $email, 'name' => $name],
                'back_urls' => $payload['back_urls'],
                'notification_url' => $payload['notification_url'],
                'external_reference' => $payload['external_reference'],
                'metadata' => $payload['metadata'],
                // não logar headers/credenciais
            ]);

            // --- Idempotência: importantíssimo ---
            $reqOpts = new RequestOptions();
            $reqOpts->setCustomHeaders([
                'X-Idempotency-Key: ' . bin2hex(random_bytes(16))
            ]);

            $pref = $client->create($payload, $reqOpts);

            $out = [
                'id'                 => $pref->id,
                'init_point'         => $pref->init_point ?? null,
                'sandbox_init_point' => $pref->sandbox_init_point ?? null,
                'external_reference' => $externalRef,
            ];

            LogService::info('MP Preference criada', $out);
            return $out;
        } catch (MPApiException $e) {
            $api = method_exists($e, 'getApiResponse') ? $e->getApiResponse() : null;
            LogService::error('MPApiException ao criar preference', [
                'message'  => $e->getMessage(),
                'response' => $api ? $api->getContent() : null,
            ]);
            throw new \RuntimeException('Mercado Pago API error');
        }
    }
}
