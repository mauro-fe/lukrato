<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Admin;

use PHPUnit\Framework\TestCase;

class AiAdminEnvBoundaryTest extends TestCase
{
    public function testAiAdminWorkflowServiceDoesNotReadEnvironmentDirectly(): void
    {
        $file = 'Application/Services/Admin/AiAdminWorkflowService.php';
        $contents = file_get_contents($file);

        $this->assertIsString($contents, sprintf('Nao foi possivel ler %s', $file));
        $this->assertDoesNotMatchRegularExpression(
            '/\$_ENV|getenv\s*\(/i',
            $contents,
            'AiAdminWorkflowService nao deve ler ambiente diretamente.'
        );
    }
}
