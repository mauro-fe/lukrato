<?php

declare(strict_types=1);

namespace Application\Config;

final class TelegramRuntimeConfig
{
    private const DEFAULT_BOT_USERNAME = 'LukratoBot';

    public function botToken(): string
    {
        return $this->string('TELEGRAM_BOT_TOKEN', '');
    }

    public function webhookSecret(): string
    {
        return $this->string('TELEGRAM_WEBHOOK_SECRET', '');
    }

    public function botUsername(): string
    {
        return $this->nullableString('TELEGRAM_BOT_USERNAME') ?? self::DEFAULT_BOT_USERNAME;
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

        if ($value !== false && $value !== null) {
            return $value;
        }

        return $default;
    }
}
