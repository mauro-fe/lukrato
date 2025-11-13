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
        $domain = $_SERVER['HTTP_HOST'] ?? '';

        return [
            'lifetime' => 0,
            'path' => '/',
            'domain' => $domain,
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax'
        ];
    }

    private function isSecureConnection(): bool
    {
        return !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    }
}
