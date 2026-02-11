<?php

declare(strict_types=1);

namespace Application\Bootstrap;

class SecurityHeaders
{
    private array $securityHeaders = [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options'        => 'DENY',
        'X-XSS-Protection'       => '1; mode=block',
        'Referrer-Policy'        => 'strict-origin-when-cross-origin',
        'Permissions-Policy'     => 'geolocation=(), microphone=(), camera=()',
    ];

    /**
     * Content Security Policy
     * Protege contra XSS e injeção de scripts maliciosos
     */
    private function getCSP(): string
    {
        $directives = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://cdn.tailwindcss.com https://unpkg.com https://accounts.google.com https://apis.google.com",
            "style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://unpkg.com https://fonts.googleapis.com",
            "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com data:",
            "img-src 'self' data: https: blob:",
            "connect-src 'self' https://accounts.google.com https://apis.google.com https://www.googleapis.com",
            "frame-src 'self' https://accounts.google.com",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
        ];
        
        return implode('; ', $directives);
    }

    public function apply(): void
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        $allowedOrigins = [
            'https://lukrato.com.br/',
            'https://www.lukrato.com.br/',
        ];

        if (in_array($origin, $allowedOrigins, true)) {
            header("Access-Control-Allow-Origin: {$origin}");
            header('Access-Control-Allow-Credentials: true');
        }

        header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

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
