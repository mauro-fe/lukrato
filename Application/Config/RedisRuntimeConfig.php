<?php

declare(strict_types=1);

namespace Application\Config;

final class RedisRuntimeConfig
{
    public function host(): string
    {
        return $this->string('REDIS_HOST', '127.0.0.1');
    }

    public function port(): int
    {
        return (int) $this->value('REDIS_PORT', 6379);
    }

    public function password(): ?string
    {
        $value = trim((string) $this->value('REDIS_PASSWORD', ''));

        return $value !== '' ? $value : null;
    }

    public function database(): int
    {
        return (int) $this->value('REDIS_DATABASE', 0);
    }

    /**
     * @return array<string, mixed>
     */
    public function webhookQueueConnection(): array
    {
        return [
            'scheme' => 'tcp',
            'host' => $this->host(),
            'port' => $this->port(),
        ];
    }

    private function string(string $key, string $default): string
    {
        return trim((string) $this->value($key, $default));
    }

    private function value(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $_ENV) && $_ENV[$key] !== null) {
            return $_ENV[$key];
        }

        $value = getenv($key);

        if ($value !== false && $value !== null) {
            return $value;
        }

        return $default;
    }
}
