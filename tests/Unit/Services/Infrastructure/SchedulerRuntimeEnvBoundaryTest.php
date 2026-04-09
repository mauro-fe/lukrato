<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Infrastructure;

use PHPUnit\Framework\TestCase;

class SchedulerRuntimeEnvBoundaryTest extends TestCase
{
    public function testSchedulerInfrastructureFilesDoNotReadEnvironmentDirectly(): void
    {
        $files = [
            'Application/Services/Infrastructure/SchedulerTaskRunner.php',
            'Application/Services/Infrastructure/SchedulerExecutionLock.php',
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
