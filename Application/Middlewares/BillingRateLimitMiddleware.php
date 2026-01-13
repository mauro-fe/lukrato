<?php

namespace Application\Middlewares;

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
    private const PREFIX = 'rate_limit:';

    // Limites por tipo
    private const LIMIT_GENERAL_PER_MINUTE = 100;
    private const LIMIT_BILLING_PER_MINUTE = 10;
    private const LIMIT_PER_HOUR = 1000;

    // Endpoints sensíveis
    private const BILLING_ENDPOINTS = [
        '/api/premium/checkout',
        '/api/premium/cancel',
        '/api/webhook/asaas',
    ];

    public function __construct()
    {
        // Usar Redis se disponível, senão fallback para sessão/arquivo
        try {
            if (class_exists(RedisClient::class)) {
                $this->redis = new RedisClient([
                    'scheme' => 'tcp',
                    'host'   => $_ENV['REDIS_HOST'] ?? '127.0.0.1',
                    'port'   => $_ENV['REDIS_PORT'] ?? 6379,
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

        // Determinar se é endpoint de cobrança
        $isBillingEndpoint = $this->isBillingEndpoint($path);

        // Verificar limites
        if ($isBillingEndpoint) {
            if (!$this->checkLimit($ip, 'billing_minute', self::LIMIT_BILLING_PER_MINUTE, 60)) {
                $this->sendRateLimitError('Muitas tentativas de cobrança. Aguarde 1 minuto.');
                return false;
            }
        }

        // Limite geral por minuto
        if (!$this->checkLimit($ip, 'minute', self::LIMIT_GENERAL_PER_MINUTE, 60)) {
            $this->sendRateLimitError('Muitas requisições. Aguarde 1 minuto.');
            return false;
        }

        // Limite por hora
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
            // Usar Redis (mais eficiente)
            $current = (int)$this->redis->get($key);

            if ($current >= $limit) {
                return false;
            }

            $this->redis->incr($key);
            if ($current === 0) {
                $this->redis->expire($key, $ttl);
            }

            return true;
        } else {
            // Fallback para arquivo (não ideal, mas funcional)
            return $this->checkLimitFile($key, $limit, $ttl);
        }
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
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR',
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                // Se for lista, pegar o primeiro
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
        http_response_code(429);
        header('Content-Type: application/json');
        header('Retry-After: 60');

        Response::error($message, 429);
        exit;
    }
}
