<?php

declare(strict_types=1);

namespace Application\Config;

final class InfrastructureRuntimeConfig
{
    public function appEnvironment(): string
    {
        if (defined('APP_ENV') && APP_ENV !== null && APP_ENV !== '') {
            return strtolower(trim((string) APP_ENV));
        }

        return strtolower($this->string('APP_ENV', 'production'));
    }

    public function isDevelopment(): bool
    {
        return $this->appEnvironment() === 'development';
    }

    /**
     * @return list<string>
     */
    public function allowedDomains(): array
    {
        return $this->csv('ALLOWED_DOMAINS', 'lukrato.com.br,www.lukrato.com.br');
    }

    /**
     * @return list<string>
     */
    public function allowedOrigins(): array
    {
        $configured = $this->csv('ALLOWED_ORIGINS', '');

        if ($configured !== []) {
            return array_values(array_unique(array_map(
                static fn(string $origin): string => rtrim($origin, '/'),
                $configured
            )));
        }

        return [
            'https://lukrato.com.br',
            'https://www.lukrato.com.br',
        ];
    }

    /**
     * @return list<string>
     */
    public function trustedProxies(): array
    {
        return $this->csv('TRUSTED_PROXIES', '');
    }

    public function sessionDriver(): string
    {
        return strtolower($this->string('SESSION_DRIVER', 'file'));
    }

    public function configuredStoragePath(): ?string
    {
        return $this->nullableString('STORAGE_PATH');
    }

    public function schedulerLockBaseDirectory(): string
    {
        return $this->configuredStoragePath()
            ?? (defined('BASE_PATH') ? BASE_PATH . '/storage' : sys_get_temp_dir());
    }

    public function adminModulesBasePath(): string
    {
        return rtrim(
            $this->nullableString('ADMIN_MODULES_PATH')
                ?? (defined('BASE_PATH') ? BASE_PATH . '/Application/Modules' : 'Application/Modules'),
            "\\/"
        );
    }

    public function legacyApiSunsetTimestamp(): ?int
    {
        $configured = $this->nullableString('LEGACY_API_SUNSET');

        if ($configured === null) {
            return null;
        }

        $timestamp = strtotime($configured);

        return $timestamp !== false ? $timestamp : null;
    }

    /**
     * @return list<string>
     */
    private function csv(string $key, string $default): array
    {
        $value = (string) $this->value($key, $default);

        return array_values(array_filter(array_map('trim', explode(',', $value))));
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
