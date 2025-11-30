<?php

namespace Application\Services;

use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Client\Common\RequestOptions;
use MercadoPago\Exceptions\MPApiException;

final class MercadoPagoService
{
    public function __construct()
    {
        self::configureSdk();
    }

    private static function currentEnv(): string
    {
        return strtolower($_ENV['MP_ENV'] ?? 'production');
    }

    public static function resolveAccessToken(): string
    {
        $live    = trim($_ENV['MP_ACCESS_TOKEN_LIVE'] ?? $_ENV['MP_ACCESS_TOKEN'] ?? '');
        $sandbox = trim($_ENV['MP_ACCESS_TOKEN_SANDBOX'] ?? $_ENV['MP_TEST_ACCESS_TOKEN'] ?? '');

        return self::currentEnv() === 'sandbox'
            ? ($sandbox ?: $live)
            : $live;
    }

    public static function resolvePublicKey(): string
    {
        $env = strtolower($_ENV['MP_ENV'] ?? 'production');

        $sandbox = trim($_ENV['MP_PUBLIC_KEY_SANDBOX'] ?? ($_ENV['MP_TEST_PUBLIC_KEY'] ?? ''));
        $live    = trim($_ENV['MP_PUBLIC_KEY_LIVE'] ?? ($_ENV['MP_PUBLIC_KEY'] ?? ''));

        $startsWith = function (string $haystack, string $needle): bool {
            return $needle === '' || strncmp($haystack, $needle, strlen($needle)) === 0;
        };

        if ($env === 'sandbox') {
            if ($sandbox) {
                if (!$startsWith($sandbox, 'TEST-')) {
                    LogService::warning('[MP] PUBLIC_KEY sandbox não começa com TEST-', ['prefix' => substr($sandbox, 0, 5)]);
                }
                LogService::info('[MP] resolvePublicKey sandbox', ['prefix' => substr($sandbox, 0, 5)]);
                return $sandbox;
            }
            if ($live) {
                LogService::warning('[MP] faltou sandbox key; usando live como fallback', ['prefix' => substr($live, 0, 7)]);
                return $live;
            }
        } else {
            if ($live) {
                if (!$startsWith($live, 'APP_USR-')) {
                    LogService::warning('[MP] PUBLIC_KEY live não começa com APP_USR-', ['prefix' => substr($live, 0, 7)]);
                }
                LogService::info('[MP] resolvePublicKey live', ['prefix' => substr($live, 0, 7)]);
                return $live;
            }
            if ($sandbox) {
                LogService::warning('[MP] faltou live key; usando sandbox como fallback', ['prefix' => substr($sandbox, 0, 5)]);
                return $sandbox;
            }
        }

        LogService::error('[MP] Nenhuma PUBLIC_KEY encontrada no .env', ['env' => $env]);
        return '';
    }



    public static function configureSdk(): void
    {
        $token = self::resolveAccessToken();
        if (!$token) {
            LogService::error('MP access token ausente no .env', ['env' => self::currentEnv()]);
            throw new \RuntimeException('Credenciais do Mercado Pago ausentes.');
        }

        MercadoPagoConfig::setAccessToken($token);
        LogService::info('MercadoPagoConfig token setado (ok)', [
            'env'          => self::currentEnv(),
            'token_origin' => self::currentEnv() === 'sandbox' && !empty($_ENV['MP_ACCESS_TOKEN_SANDBOX'])
                ? 'sandbox'
                : 'live',
        ]);
    }

    private function base(string $path = ''): string
    {
        $raw  = $_ENV['MP_CALLBACK_BASE'] ?? (defined('BASE_URL') ? BASE_URL : '/');
        $base = rtrim($raw, '/') . '/';
        return $base . ltrim($path, '/');
    }

    private function isHttps(string $url): bool
    {
        return stripos($url, 'https://') === 0;
    }

    public function createCheckoutPreference(array $data): array
    {
        $amount = (float)($data['amount'] ?? 0);
        $email  = (string)($data['email'] ?? '');
        $userId = (int)($data['user_id'] ?? 0);
        $title  = (string)($data['title'] ?? 'Assinatura Lukrato Pro');
        $name   = (string)($data['name'] ?? '');

        if ($amount <= 0 || !$email || $userId <= 0) {
            LogService::error('Preference inválida: amount/email/user_id', compact('amount', 'email', 'userId'));
            throw new \InvalidArgumentException('Dados inválidos para criar preferência.');
        }

        $client   = new PreferenceClient();
        $success  = $this->base('billing?status=success');
        $pending  = $this->base('billing?status=pending');
        $failure  = $this->base('billing?status=failure');
        $notify   = $this->base('api/webhooks/mercadopago');
        $extRef   = 'user_' . $userId . '_lukrato_' . uniqid();
        $isSandbox = strtolower($_ENV['MP_ENV'] ?? '') === 'sandbox';

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
            'back_urls'          => [
                'success' => $success,
                'pending' => $pending,
                'failure' => $failure,
            ],
            'notification_url'   => $notify,
            'external_reference' => $extRef,
            'metadata' => [
                'user_id'  => $userId,
                'username' => (string)($data['username'] ?? ''),
                'origin'   => 'lukrato_billing',
            ],
        ];

        if ($this->isHttps($success) && $this->isHttps($pending) && $this->isHttps($failure)) {
            $payload['auto_return'] = 'approved';
        } else {
            LogService::info('auto_return omitido (URLs não-HTTPS em dev)', compact('success', 'pending', 'failure'));
        }

        try {
            LogService::info('MP Preference create: payload', [
                'items' => $payload['items'],
                'payer' => ['email' => $email, 'name' => $name],
                'back_urls' => $payload['back_urls'],
                'notification_url' => $payload['notification_url'],
                'external_reference' => $payload['external_reference'],
                'metadata' => $payload['metadata'],
            ]);

            $reqOpts = new RequestOptions();
            $reqOpts->setCustomHeaders(['X-Idempotency-Key: ' . bin2hex(random_bytes(16))]);

            $pref = $client->create($payload, $reqOpts);

            $out = [
                'id'                 => $pref->id,
                'collector_id'       => $pref->collector_id ?? null,
                'init_point'         => $pref->init_point         ?? null,
                'sandbox_init_point' => $pref->sandbox_init_point ?? null,
                'external_reference' => $extRef,
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