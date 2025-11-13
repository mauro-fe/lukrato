<?php

declare(strict_types=1);

namespace Application\Bootstrap;

class SecurityHeaders
{
    private array $headers = [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'DENY',
        'X-XSS-Protection' => '1; mode=block'
    ];

    public function apply(): void
    {
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }
    }
}
