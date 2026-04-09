<?php

declare(strict_types=1);

namespace Application\Config;

final class ReferralRuntimeConfig
{
    public function developmentRegistrationAntifraudBypassEnabled(): bool
    {
        if (defined('DEV_BYPASS_REGISTRATION_ANTIFRAUD')) {
            return (bool) DEV_BYPASS_REGISTRATION_ANTIFRAUD;
        }

        return filter_var($this->value('DEV_BYPASS_REGISTRATION_ANTIFRAUD', false), FILTER_VALIDATE_BOOLEAN);
    }

    public function appEnvironment(): string
    {
        if (defined('APP_ENV')) {
            return (string) APP_ENV;
        }

        return trim((string) $this->value('APP_ENV', 'production'));
    }

    public function hashSecret(): string
    {
        return trim((string) $this->value('APP_KEY', 'lukrato_secret'));
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
