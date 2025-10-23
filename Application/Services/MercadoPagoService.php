<?php
// Application/Services/MercadoPagoService.php
namespace Application\Services;

use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Preference\PreferenceClient;
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
        $client = new PreferenceClient();

        $success = $this->base('billing?status=success');
        $pending = $this->base('billing?status=pending');
        $failure = $this->base('billing?status=failure');
        $notify  = $this->base('api/webhooks/mercadopago');

        $payload = [
            'items' => [[
                'title'       => $data['title'] ?? 'Assinatura Lukrato Pro',
                'quantity'    => 1,
                'currency_id' => 'BRL',
                'unit_price'  => (float)($data['amount'] ?? 12.00),
            ]],
            'payer' => [
                'email' => $data['email'],
                'name'  => $data['name'] ?? '',
            ],
            'back_urls' => [
                'success' => $success,
                'pending' => $pending,
                'failure' => $failure,
            ],
            'notification_url' => $notify,
            'metadata' => [
                'user_id'  => (string)($data['user_id'] ?? 0),
                'username' => (string)($data['username'] ?? ''),
                'origin'   => 'lukrato_billing',
            ],
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
            LogService::info('MP Preference create: payload', $payload);
            $pref = $client->create($payload);
            $out = [
                'id'                 => $pref->id,
                'init_point'         => $pref->init_point ?? null,
                'sandbox_init_point' => $pref->sandbox_init_point ?? null,
            ];
            LogService::info('MP Preference criada', $out);
            return $out;
        } catch (MPApiException $e) {
            $api = method_exists($e, 'getApiResponse') ? $e->getApiResponse() : null;
            LogService::error('MPApiException ao criar preference', [
                'message' => $e->getMessage(),
                'response' => $api ? $api->getContent() : null,
            ]);
            throw new \RuntimeException('Mercado Pago API error');
        }
    }
}
