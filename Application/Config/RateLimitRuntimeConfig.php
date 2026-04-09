<?php

declare(strict_types=1);

namespace Application\Config;

final class RateLimitRuntimeConfig
{
    public function maxAttempts(): int
    {
        return (int) $this->value('RATELIMIT_MAX_ATTEMPTS', 60);
    }

    public function timeWindow(): int
    {
        return (int) $this->value('RATELIMIT_TIME_WINDOW', 60);
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
