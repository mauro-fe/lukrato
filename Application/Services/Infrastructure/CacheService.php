<?php

namespace Application\Services\Infrastructure;

use Application\Core\Exceptions\ValidationException;
use Application\Enums\LogCategory;
use Application\Enums\LogLevel;
use Predis\Client as PredisClient;

class CacheService
{
    private const RATE_LIMIT_STORAGE_PREFIX = 'rate_limit_fallback:';

    /**
     * @var array<string, array<int, int>>
     */
    private static array $memoryRateLimitStore = [];

    private ?PredisClient $redis = null;
    private bool $enabled = false;
    private string $fileCacheDir;

    public function __construct()
    {
        $this->fileCacheDir = ($_ENV['STORAGE_PATH'] ?? dirname(__DIR__, 2) . '/storage') . '/cache';
        $this->enabled = ($_ENV['REDIS_ENABLED'] ?? 'false') === 'true';

        if (!$this->enabled) {
            return;
        }

        $config = [
            'scheme' => 'tcp',
            'host' => $_ENV['REDIS_HOST'] ?? '127.0.0.1',
            'port' => (int) ($_ENV['REDIS_PORT'] ?? 6379),
            'timeout' => (float) ($_ENV['REDIS_TIMEOUT'] ?? 0.5),
            'read_write_timeout' => (float) ($_ENV['REDIS_RW_TIMEOUT'] ?? 1.0),
            'database' => (int) ($_ENV['REDIS_DB'] ?? 0),
        ];

        $password = $_ENV['REDIS_PASSWORD'] ?? null;
        if ($password !== null && $password !== '' && strtolower($password) !== 'null') {
            $config['password'] = $password;
        }

        try {
            $this->redis = new PredisClient($config);
            $this->redis->ping();
        } catch (\Throwable $e) {
            $this->enabled = false;
            $this->redis = null;
            LogService::safeErrorLog('[CacheService] Redis indisponivel, usando fallback local: ' . $e->getMessage());
        }
    }

    public function isEnabled(): bool
    {
        return $this->enabled && $this->redis !== null;
    }

    private function ensureFileCacheDirectory(): bool
    {
        if (is_dir($this->fileCacheDir)) {
            return true;
        }

        return @mkdir($this->fileCacheDir, 0755, true);
    }

    private function fileCachePath(string $key): string
    {
        return $this->fileCacheDir . '/' . sha1($key) . '.cache';
    }

    private function fileSet(string $key, mixed $value, int $ttl): bool
    {
        if (!$this->ensureFileCacheDirectory()) {
            return false;
        }

        $payload = json_encode([
            'expires' => time() + $ttl,
            'value' => $value,
        ], JSON_THROW_ON_ERROR);

        return file_put_contents($this->fileCachePath($key), $payload, LOCK_EX) !== false;
    }

    private function fileGet(string $key, mixed $default = null): mixed
    {
        $path = $this->fileCachePath($key);
        if (!file_exists($path)) {
            return $default;
        }

        $raw = file_get_contents($path);
        if ($raw === false) {
            return $default;
        }

        $data = json_decode($raw, true);
        if (!is_array($data) || !isset($data['expires'], $data['value'])) {
            @unlink($path);

            return $default;
        }

        if ($data['expires'] < time()) {
            @unlink($path);

            return $default;
        }

        return $data['value'];
    }

    private function fileForget(string $key): bool
    {
        $path = $this->fileCachePath($key);
        if (file_exists($path)) {
            return @unlink($path);
        }

        return false;
    }

    /**
     * @throws ValidationException
     */
    public function checkRateLimit(string $key, int $limit = 5, int $seconds = 60): void
    {
        $rateKey = 'rate_limit:' . $key;

        if ($this->isEnabled()) {
            try {
                $current = $this->redis->incr($rateKey);

                if ($current === 1) {
                    $this->redis->expire($rateKey, $seconds);
                }

                if ($current > $limit) {
                    $this->logRateLimitExceeded($key, $limit, $seconds, 'redis');
                    throw $this->buildRateLimitException($seconds);
                }

                return;
            } catch (\Throwable $e) {
                if ($e instanceof ValidationException) {
                    throw $e;
                }

                LogService::warning('[CacheService] Redis indisponivel para rate limit, usando fallback seguro.', [
                    'key' => $key,
                    'limit' => $limit,
                    'window' => $seconds,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($this->applyFileRateLimit($key, $limit, $seconds)) {
            return;
        }

        $this->applyMemoryRateLimit($key, $limit, $seconds);
    }

    public function set(string $key, mixed $value, int $ttl = 300): bool
    {
        if (!$this->isEnabled()) {
            return $this->fileSet($key, $value, $ttl);
        }

        try {
            try {
                $json = json_encode($value, JSON_THROW_ON_ERROR);
                $payload = json_encode(['fmt' => 'json', 'v' => $json], JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                $payload = json_encode(['fmt' => 'ser', 'v' => serialize($value)]);
            }

            return (bool) $this->redis->setex($key, $ttl, $payload);
        } catch (\Throwable $e) {
            LogService::safeErrorLog("[CacheService] SET falhou ({$key}): " . $e->getMessage());

            return $this->fileSet($key, $value, $ttl);
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (!$this->isEnabled()) {
            return $this->fileGet($key, $default);
        }

        try {
            $raw = $this->redis->get($key);
            if ($raw === null) {
                return $default;
            }

            $decoded = json_decode((string) $raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded) && isset($decoded['fmt'], $decoded['v'])) {
                if ($decoded['fmt'] === 'json') {
                    try {
                        return json_decode($decoded['v'], true, 512, JSON_THROW_ON_ERROR);
                    } catch (\JsonException) {
                        return $default;
                    }
                }

                if ($decoded['fmt'] === 'ser') {
                    try {
                        $unserialized = unserialize($decoded['v'], ['allowed_classes' => false]);

                        return $unserialized === false && $decoded['v'] !== serialize(false)
                            ? $default
                            : $unserialized;
                    } catch (\Throwable) {
                        return $default;
                    }
                }
            }

            return $default;
        } catch (\Throwable $e) {
            LogService::safeErrorLog("[CacheService] GET falhou ({$key}): " . $e->getMessage());

            return $this->fileGet($key, $default);
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
        if (!$this->isEnabled()) {
            return $this->fileForget($key);
        }

        try {
            return (int) $this->redis->del([$key]) > 0;
        } catch (\Throwable $e) {
            LogService::safeErrorLog("[CacheService] FORGET falhou ({$key}): " . $e->getMessage());

            return $this->fileForget($key);
        }
    }

    public function flush(): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        try {
            return (string) $this->redis->flushdb() === 'OK';
        } catch (\Throwable $e) {
            LogService::safeErrorLog('[CacheService] FLUSH falhou: ' . $e->getMessage());

            return false;
        }
    }

    private function buildRateLimitException(int $seconds): ValidationException
    {
        return new ValidationException(
            ['rate_limit' => "Muitas tentativas. Aguarde {$seconds} segundos e tente novamente."],
            'Rate limit exceeded',
            429
        );
    }

    private function applyFileRateLimit(string $key, int $limit, int $seconds): bool
    {
        if (!$this->ensureFileCacheDirectory()) {
            LogService::warning('[CacheService] File cache indisponivel para rate limit, tentando fallback em memoria.', [
                'key' => $key,
                'limit' => $limit,
                'window' => $seconds,
            ]);

            return false;
        }

        $path = $this->fileCachePath(self::RATE_LIMIT_STORAGE_PREFIX . $key);
        $handle = @fopen($path, 'c+');

        if ($handle === false) {
            LogService::warning('[CacheService] Nao foi possivel abrir storage de rate limit em arquivo.', [
                'key' => $key,
                'limit' => $limit,
                'window' => $seconds,
            ]);

            return false;
        }

        $now = time();

        try {
            if (!flock($handle, LOCK_EX)) {
                return false;
            }

            rewind($handle);
            $raw = stream_get_contents($handle);
            $data = is_string($raw) && $raw !== '' ? json_decode($raw, true) : [];
            $attempts = $this->normalizeRateLimitAttempts($data['attempts'] ?? [], $now, $seconds);

            if (count($attempts) >= $limit) {
                $this->logRateLimitExceeded($key, $limit, $seconds, 'file');
                throw $this->buildRateLimitException($seconds);
            }

            $attempts[] = $now;
            $payload = json_encode([
                'expires' => $now + $seconds,
                'attempts' => $attempts,
            ], JSON_THROW_ON_ERROR);

            rewind($handle);
            ftruncate($handle, 0);
            fwrite($handle, $payload);
            fflush($handle);

            return true;
        } catch (\Throwable $e) {
            if ($e instanceof ValidationException) {
                throw $e;
            }

            LogService::warning('[CacheService] Rate limit em arquivo falhou, tentando fallback em memoria.', [
                'key' => $key,
                'limit' => $limit,
                'window' => $seconds,
                'error' => $e->getMessage(),
            ]);

            return false;
        } finally {
            @flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    private function applyMemoryRateLimit(string $key, int $limit, int $seconds): void
    {
        $now = time();
        $attempts = self::$memoryRateLimitStore[$key] ?? [];
        $attempts = $this->normalizeRateLimitAttempts($attempts, $now, $seconds);

        if (count($attempts) >= $limit) {
            $this->logRateLimitExceeded($key, $limit, $seconds, 'memory');
            throw $this->buildRateLimitException($seconds);
        }

        $attempts[] = $now;
        self::$memoryRateLimitStore[$key] = $attempts;
    }

    /**
     * @param mixed $attempts
     * @return array<int, int>
     */
    private function normalizeRateLimitAttempts(mixed $attempts, int $now, int $seconds): array
    {
        if (!is_array($attempts)) {
            return [];
        }

        $normalized = [];

        foreach ($attempts as $attempt) {
            if (is_int($attempt)) {
                $timestamp = $attempt;
            } elseif (is_numeric($attempt)) {
                $timestamp = (int) $attempt;
            } else {
                continue;
            }

            if (($now - $timestamp) < $seconds) {
                $normalized[] = $timestamp;
            }
        }

        return $normalized;
    }

    private function logRateLimitExceeded(string $key, int $limit, int $seconds, string $backend): void
    {
        LogService::persist(
            level: LogLevel::WARNING,
            category: LogCategory::SECURITY,
            message: 'Rate limit excedido',
            context: [
                'key' => $key,
                'limit' => $limit,
                'window' => $seconds,
                'backend' => $backend,
            ]
        );
    }
}
