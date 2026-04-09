<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use PHPUnit\Framework\TestCase;

class CoreSupportRuntimeEnvBoundaryTest extends TestCase
{
    public function testCoreAndSupportHotspotsDoNotReadEnvironmentDirectly(): void
    {
        $files = [
            'Application/Core/Routing/HttpExceptionHandler.php',
            'Application/Core/Request.php',
            'Application/Lib/Auth.php',
            'Application/Support/Admin/AdminModuleRegistry.php',
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
