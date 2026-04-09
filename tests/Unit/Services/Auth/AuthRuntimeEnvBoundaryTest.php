<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Auth;

use PHPUnit\Framework\TestCase;

class AuthRuntimeEnvBoundaryTest extends TestCase
{
    public function testAuthServicesDoNotReadEnvironmentDirectly(): void
    {
        $files = [
            'Application/Services/Auth/GoogleAuthService.php',
            'Application/Services/Auth/EmailVerificationService.php',
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
