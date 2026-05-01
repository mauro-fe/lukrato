<?php

declare(strict_types=1);

namespace Application\Config;

final class CommunicationRuntimeConfig
{
    public function mailHost(): string
    {
        return $this->string('MAIL_HOST', '');
    }

    public function mailUsername(): string
    {
        return $this->string('MAIL_USERNAME', '');
    }

    public function mailPassword(): string
    {
        return $this->string('MAIL_PASSWORD', '');
    }

    public function mailPort(): int
    {
        return (int) $this->value('MAIL_PORT', 587);
    }

    public function mailEncryption(): string
    {
        return $this->string('MAIL_ENCRYPTION', 'tls');
    }

    public function mailFromEmail(): string
    {
        return $this->nullableString('MAIL_FROM')
            ?? $this->nullableString('MAIL_USERNAME')
            ?? 'no-reply@localhost';
    }

    public function mailFromName(): string
    {
        return $this->string('MAIL_FROM_NAME', 'Lukrato');
    }

    public function mailBcc(): ?string
    {
        return $this->nullableString('MAIL_BCC');
    }

    public function supportEmail(): string
    {
        return $this->nullableString('SUPPORT_EMAIL') ?? $this->mailFromEmail();
    }

    public function mailInboxEmail(): string
    {
        return $this->nullableString('MAIL_USERNAME')
            ?? $this->nullableString('MAIL_FROM')
            ?? 'lukratosistema@gmail.com';
    }

    public function configuredAppUrl(): string
    {
        return rtrim($this->string('APP_URL', ''), '/');
    }

    public function appUrl(): string
    {
        $configured = $this->configuredAppUrl();
        if ($configured !== '') {
            return $configured;
        }

        if (defined('BASE_URL')) {
            return rtrim((string) BASE_URL, '/');
        }

        return '';
    }

    public function debugEnabled(): bool
    {
        return strtolower($this->string('APP_DEBUG', 'false')) === 'true';
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
