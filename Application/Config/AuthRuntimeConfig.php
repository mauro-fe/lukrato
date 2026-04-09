<?php

declare(strict_types=1);

namespace Application\Config;

final class AuthRuntimeConfig
{
    public function googleClientId(): string
    {
        return $this->string('GOOGLE_CLIENT_ID', '');
    }

    public function googleClientSecret(): string
    {
        return $this->string('GOOGLE_CLIENT_SECRET', '');
    }

    public function googleRedirectUri(): string
    {
        return $this->string('GOOGLE_REDIRECT_URI', '');
    }

    public function hasGoogleOauthCredentials(): bool
    {
        return $this->googleClientId() !== ''
            && $this->googleClientSecret() !== ''
            && $this->googleRedirectUri() !== '';
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
