<?php

namespace Application\Services;

use Predis\Client;

class CacheService
{
    private Client $redis;
    private bool $isRedisConnected = false;

    public function __construct()
    {
        try {
            $this->redis = new Client([
                'scheme' => 'tcp',
                'host'   => $_ENV['REDIS_HOST'] ?? '127.0.0.1',
                'port'   => $_ENV['REDIS_PORT'] ?? 6379,
                'timeout' => 0.5,
            ]);

            // Tenta pingar o Redis — se falhar, ignora e continua
            $this->redis->ping();
            $this->isRedisConnected = true;
        } catch (\Exception $e) {
            $this->isRedisConnected = false;
            error_log("Redis desativado ou indisponível: " . $e->getMessage());
        }
    }

    public function set(string $key, $value, int $ttl): bool
    {
        if (!$this->isRedisConnected) return false;

        try {
            return (bool) $this->redis->setex($key, $ttl, serialize($value));
        } catch (\Exception $e) {
            error_log("Redis SET falhou: " . $e->getMessage());
            return false;
        }
    }

    public function get(string $key, $default = null)
    {
        if (!$this->isRedisConnected) return $default;

        try {
            $cached = $this->redis->get($key);
            return $cached !== null ? unserialize($cached) : $default;
        } catch (\Exception $e) {
            error_log("Redis GET falhou: " . $e->getMessage());
            return $default;
        }
    }

    public function remember(string $key, int $ttl, callable $callback)
    {
        $cached = $this->get($key);

        if ($cached !== null) {
            return $cached;
        }

        $value = $callback();
        $this->set($key, $value, $ttl);

        return $value;
    }

    public function forget(string $key): bool
    {
        if (!$this->isRedisConnected) return false;

        try {
            return (bool) $this->redis->del([$key]);
        } catch (\Exception $e) {
            error_log("Redis FORGET falhou: " . $e->getMessage());
            return false;
        }
    }

    public function flush(): bool
    {
        if (!$this->isRedisConnected) return false;

        try {
            return $this->redis->flushdb();
        } catch (\Exception $e) {
            error_log("Redis FLUSH falhou: " . $e->getMessage());
            return false;
        }
    }
}
