<?php

declare(strict_types=1);

namespace Application\Bootstrap;

class SecurityHeaders
{
    private array $securityHeaders = [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options'        => 'DENY',
        'X-XSS-Protection'      => '1; mode=block',
    ];

    public function apply(): void
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        $allowedOrigins = [
            'https://lukrato.com.br',
            'https://www.lukrato.com.br',
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

        // Preflight (OBRIGATÓRIO para fetch)
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }
}
