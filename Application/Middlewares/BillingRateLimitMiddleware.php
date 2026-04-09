<?php

namespace Application\Middlewares;

use Application\Config\RedisRuntimeConfig;
use Application\Container\ApplicationContainer;
use Application\Core\Exceptions\HttpResponseException;
use Application\Core\Request;
use Application\Core\Response;
use Predis\Client as RedisClient;

/**
 * Middleware de Rate Limiting para evitar abuso de API
 *
 * Limites:
 * - 100 requisições por minuto por IP (geral)
 * - 10 requisições por minuto para endpoints de cobrança
 * - 1000 requisições por hora por IP
 */
class BillingRateLimitMiddleware
{
    private ?RedisClient $redis = null;
    private RedisRuntimeConfig $runtimeConfig;
    private const PREFIX = 'rate_limit:';

    private const LIMIT_GENERAL_PER_MINUTE = 100;
    private const LIMIT_BILLING_PER_MINUTE = 10;
    private const LIMIT_PER_HOUR = 1000;

    private const BILLING_ENDPOINTS = [
        '/api/premium/checkout',
        '/api/premium/cancel',
        '/api/webhook/asaas',
    ];

    public function __construct(?RedisRuntimeConfig $runtimeConfig = null)
    {
        $this->runtimeConfig = ApplicationContainer::resolveOrNew($runtimeConfig, RedisRuntimeConfig::class);

        try {
            if (class_exists(RedisClient::class)) {
                $this->redis = new RedisClient([
                    'scheme' => 'tcp',
                    'host' => $this->runtimeConfig->host(),
                    'port' => $this->runtimeConfig->port(),
                ]);
                $this->redis->ping();
            }
        } catch (\Throwable $e) {
            $this->redis = null;
        }
    }

    public function handle(Request $request): bool
    {
        $ip = $this->getClientIp();
        $path = $request->path ?? $_SERVER['REQUEST_URI'] ?? '/';

        $isBillingEndpoint = $this->isBillingEndpoint($path);

        if ($isBillingEndpoint) {
            if (!$this->checkLimit($ip, 'billing_minute', self::LIMIT_BILLING_PER_MINUTE, 60)) {
                $this->sendRateLimitError('Muitas tentativas de cobrança. Aguarde 1 minuto.');
                return false;
            }
        }

        if (!$this->checkLimit($ip, 'minute', self::LIMIT_GENERAL_PER_MINUTE, 60)) {
            $this->sendRateLimitError('Muitas requisições. Aguarde 1 minuto.');
            return false;
        }

        if (!$this->checkLimit($ip, 'hour', self::LIMIT_PER_HOUR, 3600)) {
            $this->sendRateLimitError('Limite horário excedido. Aguarde 1 hora.');
            return false;
        }

        return true;
    }

    private function checkLimit(string $ip, string $window, int $limit, int $ttl): bool
    {
        $key = self::PREFIX . "{$ip}:{$window}";

        if ($this->redis) {
            $current = (int) $this->redis->get($key);

            if ($current >= $limit) {
                return false;
            }

            $this->redis->incr($key);
            if ($current === 0) {
                $this->redis->expire($key, $ttl);
            }

            return true;
        }

        return $this->checkLimitFile($key, $limit, $ttl);
    }

    private function checkLimitFile(string $key, int $limit, int $ttl): bool
    {
        $cacheDir = BASE_PATH . '/storage/cache/rate_limit';
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }

        $file = $cacheDir . '/' . md5($key) . '.txt';
        $data = ['count' => 0, 'expires' => time() + $ttl];

        if (file_exists($file)) {
            $existing = json_decode(file_get_contents($file), true);
            if ($existing && $existing['expires'] > time()) {
                $data = $existing;
            }
        }

        if ($data['count'] >= $limit) {
            return false;
        }

        $data['count']++;
        file_put_contents($file, json_encode($data), LOCK_EX);

        return true;
    }

    private function isBillingEndpoint(string $path): bool
    {
        foreach (self::BILLING_ENDPOINTS as $endpoint) {
            if (str_contains($path, $endpoint)) {
                return true;
            }
        }

        return false;
    }

    private function getClientIp(): string
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR',
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                if (str_contains($ip, ',')) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    private function sendRateLimitError(string $message): void
    {
        throw new HttpResponseException(
            Response::errorResponse($message, 429)->header('Retry-After', '60')
        );
    }
}
