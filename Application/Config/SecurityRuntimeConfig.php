<?php

declare(strict_types=1);

namespace Application\Config;

final class SecurityRuntimeConfig
{
    public function cpfEncryptionKey(): string
    {
        $cpfKey = $this->string('CPF_ENCRYPTION_KEY');
        if ($cpfKey !== '') {
            return $cpfKey;
        }

        return $this->string('APP_KEY');
    }

    private function string(string $key): string
    {
        return trim((string) $this->value($key, ''));
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
