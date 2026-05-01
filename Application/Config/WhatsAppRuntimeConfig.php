<?php

declare(strict_types=1);

namespace Application\Config;

final class WhatsAppRuntimeConfig
{
    public function token(): string
    {
        return $this->string('WHATSAPP_TOKEN', '');
    }

    public function phoneId(): string
    {
        return $this->string('WHATSAPP_PHONE_ID', '');
    }

    public function verifyToken(): string
    {
        return $this->string('WHATSAPP_VERIFY_TOKEN', '');
    }

    public function appSecret(): string
    {
        return $this->string('WHATSAPP_APP_SECRET', '');
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

        if ($value !== false) {
            return $value;
        }

        return $default;
    }
}
