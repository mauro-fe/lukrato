<?php

namespace Application\Services;

use Predis\Client as PredisClient;

class CacheService
{
    private ?PredisClient $redis = null;
    private bool $enabled = false;

    public function __construct()
    {
        // Certifique-se de carregar o Dotenv antes disso:
        // (no seu bootstrap)
        // Dotenv::createImmutable(base_path)->load();

        $this->enabled = ($_ENV['REDIS_ENABLED'] ?? 'false') === 'true';

        if (!$this->enabled) {
            return; // cache desabilitado via flag
        }

        // Monta config a partir do .env
        $config = [
            'scheme'             => 'tcp',
            'host'               => $_ENV['REDIS_HOST'] ?? '127.0.0.1',
            'port'               => (int)($_ENV['REDIS_PORT'] ?? 6379),
            'timeout'            => (float)($_ENV['REDIS_TIMEOUT'] ?? 0.5),
            'read_write_timeout' => (float)($_ENV['REDIS_RW_TIMEOUT'] ?? 1.0),
            'database'           => (int)($_ENV['REDIS_DB'] ?? 0),
        ];

        $password = $_ENV['REDIS_PASSWORD'] ?? null;
        if ($password !== null && $password !== '' && strtolower($password) !== 'null') {
            $config['password'] = $password;
        }

        try {
            $this->redis = new PredisClient($config);
            $this->redis->ping(); // sanity check
        } catch (\Throwable $e) {
            $this->enabled = false;
            $this->redis = null;
            error_log('[CacheService] Redis indisponível: ' . $e->getMessage());
        }
    }

    public function isEnabled(): bool
    {
        return $this->enabled && $this->redis !== null;
    }

    public function set(string $key, mixed $value, int $ttl = 300): bool
    {
        if (!$this->isEnabled()) return false;

        try {
            $payload = serialize($value);
            // setex(key, ttl, value) → string "OK"
            return (bool) $this->redis->setex($key, $ttl, $payload);
        } catch (\Throwable $e) {
            error_log("[CacheService] SET falhou ({$key}): " . $e->getMessage());
            return false;
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (!$this->isEnabled()) return $default;

        try {
            $raw = $this->redis->get($key);
            if ($raw === null) return $default;
            return @unserialize($raw) ?: $default;
        } catch (\Throwable $e) {
            error_log("[CacheService] GET falhou ({$key}): " . $e->getMessage());
            return $default;
        }
    }

    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        $cached = $this->get($key, null);
        if ($cached !== null) {
            return $cached;
        }
        $value = $callback();
        $this->set($key, $value, $ttl);
        return $value;
    }

    public function forget(string $key): bool
    {
        if (!$this->isEnabled()) return false;

        try {
            // del retorna número de chaves removidas
            return (int) $this->redis->del([$key]) > 0;
        } catch (\Throwable $e) {
            error_log("[CacheService] FORGET falhou ({$key}): " . $e->getMessage());
            return false;
        }
    }

    public function flush(): bool
    {
        if (!$this->isEnabled()) return false;

        try {
            // flushdb retorna "OK"
            return (string) $this->redis->flushdb() === 'OK';
        } catch (\Throwable $e) {
            error_log("[CacheService] FLUSH falhou: " . $e->getMessage());
            return false;
        }
    }
}
