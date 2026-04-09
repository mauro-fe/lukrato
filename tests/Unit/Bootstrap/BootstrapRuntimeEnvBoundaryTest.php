<?php

declare(strict_types=1);

namespace Tests\Unit\Bootstrap;

use PHPUnit\Framework\TestCase;

class BootstrapRuntimeEnvBoundaryTest extends TestCase
{
    public function testBootstrapHotspotsDoNotReadEnvironmentDirectly(): void
    {
        $files = [
            'Application/Bootstrap/SessionManager.php',
            'Application/Bootstrap/SessionConfig.php',
            'Application/Bootstrap/SecurityHeaders.php',
            'Application/Bootstrap/ErrorHandler.php',
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
