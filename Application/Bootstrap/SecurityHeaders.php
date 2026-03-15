<?php

declare(strict_types=1);

namespace Application\Bootstrap;

class SecurityHeaders
{
    private array $securityHeaders = [
        'X-Content-Type-Options'    => 'nosniff',
        'X-Frame-Options'           => 'DENY',
        'X-XSS-Protection'          => '1; mode=block',
        'Referrer-Policy'           => 'strict-origin-when-cross-origin',
        'Permissions-Policy'        => 'geolocation=(), microphone=(), camera=()',
        'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains; preload',
    ];

    private function isDev(): bool
    {
        return (defined('APP_ENV') && APP_ENV === 'development')
            || (($_ENV['APP_ENV'] ?? 'production') === 'development');
    }

    private function isLocalHost(string $host): bool
    {
        return in_array($host, ['localhost', '127.0.0.1', '::1'], true);
    }

    private function isLocalRequest(): bool
    {
        $hostHeader = (string) ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '');
        $host = strtolower((string) parse_url('http://' . $hostHeader, PHP_URL_HOST));

        return $this->isLocalHost($host);
    }

    private function isAllowedOrigin(string $origin): bool
    {
        if ($origin === '') {
            return false;
        }

        $normalizedOrigin = rtrim($origin, '/');

        $allowedOrigins = [
            'https://lukrato.com.br',
            'https://www.lukrato.com.br',
        ];

        if (in_array($normalizedOrigin, $allowedOrigins, true)) {
            return true;
        }

        if (!$this->isDev() && !$this->isLocalRequest()) {
            return false;
        }

        $originHost = strtolower((string) parse_url($normalizedOrigin, PHP_URL_HOST));
        $originScheme = strtolower((string) parse_url($normalizedOrigin, PHP_URL_SCHEME));
        $originPort = parse_url($normalizedOrigin, PHP_URL_PORT);

        if (!in_array($originScheme, ['http', 'https'], true)) {
            return false;
        }

        if (!$this->isLocalHost($originHost)) {
            return false;
        }

        if ($originPort === null) {
          return true;
        }

        return $originPort >= 1 && $originPort <= 65535;
    }

    /**
     * Content Security Policy
     * Protege contra XSS e injeção de scripts maliciosos
     */
    private function getCSP(): string
    {
        $connectSrc = "'self' https://lukrato.com.br https://www.lukrato.com.br https://cdn.jsdelivr.net https://cdn.tiny.cloud https://accounts.google.com https://apis.google.com https://www.googleapis.com https://challenges.cloudflare.com";

        // Em desenvolvimento, permitir conexões para localhost/Vite dev server
        if ($this->isDev()) {
            $connectSrc .= ' http://localhost http://localhost:* http://127.0.0.1 http://127.0.0.1:* ws://localhost:* ws://127.0.0.1:*';
        }

        $directives = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://unpkg.com https://cdn.tiny.cloud https://accounts.google.com https://apis.google.com https://challenges.cloudflare.com",
            "style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://unpkg.com https://fonts.googleapis.com",
            "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com data:",
            "img-src 'self' data: https: blob:",
            "connect-src {$connectSrc}",
            "frame-src 'self' https://accounts.google.com https://challenges.cloudflare.com",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
        ];

        return implode('; ', $directives);
    }

    public function apply(): void
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        if ($this->isAllowedOrigin($origin)) {
            header('Vary: Origin');
            header('Access-Control-Allow-Origin: ' . rtrim($origin, '/'));
            header('Access-Control-Allow-Credentials: true');
        }

        header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Accept, Content-Type, Authorization, X-Requested-With, X-CSRF-Token');

        // Headers de segurança
        foreach ($this->securityHeaders as $name => $value) {
            header("$name: $value");
        }

        // Content Security Policy
        header('Content-Security-Policy: ' . $this->getCSP());

        // Preflight (OBRIGATÓRIO para fetch)
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }
}
