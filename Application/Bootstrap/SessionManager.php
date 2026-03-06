<?php

declare(strict_types=1);

namespace Application\Bootstrap;

class SessionManager
{
    public function start(): void
    {
        if (session_status() !== PHP_SESSION_NONE) {
            return;
        }

        $config = $this->getSessionConfig();
        session_set_cookie_params($config);
        session_start();
    }

    private function getSessionConfig(): array
    {
        $secure = $this->isSecureConnection();
        $domain = $this->getValidDomain();

        return [
            'lifetime' => 0,
            'path' => '/',
            'domain' => $domain,
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax'
        ];
    }

    private function getValidDomain(): string
    {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $host = strtolower(explode(':', $host)[0]);

        $allowed = array_filter(array_map(
            'trim',
            explode(',', $_ENV['ALLOWED_DOMAINS'] ?? 'lukrato.com.br,www.lukrato.com.br')
        ));

        if (in_array($host, $allowed, true)) {
            return $host;
        }

        // Em desenvolvimento, aceitar localhost
        if (($_ENV['APP_ENV'] ?? 'production') === 'development' && ($host === 'localhost' || str_starts_with($host, '127.'))) {
            return $host;
        }

        return '';
    }

    private function isSecureConnection(): bool
    {
        return !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    }
}
