<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use PHPUnit\Framework\TestCase;

class AuthAndContactControllerEnvBoundaryTest extends TestCase
{
    public function testAuthAndContactControllersDoNotReadEnvironmentDirectly(): void
    {
        $files = [
            'Application/Controllers/Auth/GoogleLoginController.php',
            'Application/Controllers/Auth/GoogleCallbackController.php',
            'Application/Controllers/Api/User/ContactController.php',
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
