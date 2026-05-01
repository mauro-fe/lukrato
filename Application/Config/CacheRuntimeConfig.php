<?php

declare(strict_types=1);

namespace Application\Config;

final class CacheRuntimeConfig
{
    public function storagePath(): string
    {
        return rtrim($this->string('STORAGE_PATH', dirname(__DIR__) . '/storage'), '/\\');
    }

    public function fileCacheDirectory(): string
    {
        return $this->storagePath() . '/cache';
    }

    public function redisEnabled(): bool
    {
        return filter_var($this->value('REDIS_ENABLED', false), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @return array<string, mixed>
     */
    public function redisConnection(): array
    {
        $config = [
            'scheme' => 'tcp',
            'host' => $this->string('REDIS_HOST', '127.0.0.1'),
            'port' => (int) $this->value('REDIS_PORT', 6379),
            'timeout' => (float) $this->value('REDIS_TIMEOUT', 0.5),
            'read_write_timeout' => (float) $this->value('REDIS_RW_TIMEOUT', 1.0),
            'database' => (int) $this->value('REDIS_DB', 0),
        ];

        $password = $this->nullableString('REDIS_PASSWORD');
        if ($password !== null && strtolower($password) !== 'null') {
            $config['password'] = $password;
        }

        return $config;
    }

    private function string(string $key, string $default): string
    {
        return trim((string) $this->value($key, $default));
    }

    private function nullableString(string $key): ?string
    {
        $value = trim((string) $this->value($key, ''));

        return $value !== '' ? $value : null;
    }

    private function value(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $_ENV) && $_ENV[$key] !== null) {
            return $_ENV[$key];
        }

        $value = getenv($key);

        if ($value !== false) {
            return $value;
        }

        return $default;
    }
}
