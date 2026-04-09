<?php

declare(strict_types=1);

namespace Tests\Unit\Middlewares;

use PHPUnit\Framework\TestCase;

class MiddlewareEnvBoundaryTest extends TestCase
{
    public function testMiddlewaresDoNotReadEnvironmentDirectly(): void
    {
        $files = [
            'Application/Middlewares/RateLimitMiddleware.php',
            'Application/Middlewares/BillingRateLimitMiddleware.php',
        ];

        foreach ($files as $file) {
            $contents = file_get_contents($file);

            $this->assertIsString($contents, sprintf('Nao foi possivel ler %s', $file));
            $this->assertDoesNotMatchRegularExpression(
                '/\$_ENV|getenv\s*\(/i',
                $contents,
                sprintf('%s nao deve ler ambiente diretamente.', $file)
            );
        }
    }
}
