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

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
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
        if ($this->isDirectSecureConnection()) {
            return true;
        }

        $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';
        if (!$this->isTrustedProxy($remoteAddr)) {
            return false;
        }

        $forwardedProto = strtolower(trim(explode(',', (string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''))[0] ?? ''));
        if ($forwardedProto !== '') {
            return in_array($forwardedProto, ['https', 'wss'], true);
        }

        if (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_SSL'] ?? '')) === 'on') {
            return true;
        }

        if (strtolower((string) ($_SERVER['HTTP_FRONT_END_HTTPS'] ?? '')) === 'on') {
            return true;
        }

        $cfVisitor = (string) ($_SERVER['HTTP_CF_VISITOR'] ?? '');

        return $cfVisitor !== '' && str_contains(strtolower($cfVisitor), '"scheme":"https"');
    }

    private function isDirectSecureConnection(): bool
    {
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return true;
        }

        return (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;
    }

    private function isTrustedProxy(string $remoteAddr): bool
    {
        $trustedProxies = array_filter(array_map(
            'trim',
            explode(',', $_ENV['TRUSTED_PROXIES'] ?? getenv('TRUSTED_PROXIES') ?: '')
        ));

        return $remoteAddr !== '' && in_array($remoteAddr, $trustedProxies, true);
    }
}
