<?php

declare(strict_types=1);

namespace Application\Config;

final class AsaasRuntimeConfig
{
    private const DEFAULT_BASE_URL = 'https://sandbox.asaas.com/api/v3';
    private const DEFAULT_USER_AGENT = 'Lukrato/1.0 (PHP)';

    public function apiKey(): string
    {
        return $this->string('ASAAS_API_KEY', '');
    }

    public function baseUrl(): string
    {
        return $this->string('ASAAS_BASE_URL', self::DEFAULT_BASE_URL);
    }

    public function userAgent(): string
    {
        return $this->string('ASAAS_USER_AGENT', self::DEFAULT_USER_AGENT);
    }

    public function webhookToken(): ?string
    {
        return $this->nullableString('ASAAS_WEBHOOK_TOKEN');
    }

    public function webhookSecret(): ?string
    {
        return $this->nullableString('ASAAS_WEBHOOK_SECRET');
    }

    /**
     * @return list<string>
     */
    public function webhookAllowedIps(): array
    {
        $configured = $this->nullableString('ASAAS_WEBHOOK_IPS');
        if ($configured === null) {
            return [];
        }

        $ips = array_map(
            static fn(string $ip): string => trim($ip),
            explode(',', $configured)
        );

        return array_values(array_filter($ips, static fn(string $ip): bool => $ip !== ''));
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
