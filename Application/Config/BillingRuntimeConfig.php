<?php

declare(strict_types=1);

namespace Application\Config;

final class BillingRuntimeConfig
{
    public function appUrl(): string
    {
        return $this->string('APP_URL', '');
    }

    public function adminEmail(): ?string
    {
        return $this->nullableString('ADMIN_EMAIL');
    }

    public function slackWebhookUrl(): ?string
    {
        return $this->nullableString('SLACK_WEBHOOK_URL');
    }

    private function string(string $key, string $default): string
    {
        $value = $this->value($key, $default);

        return trim((string) $value);
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
