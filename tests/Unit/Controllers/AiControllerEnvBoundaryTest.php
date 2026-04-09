<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use PHPUnit\Framework\TestCase;

class AiControllerEnvBoundaryTest extends TestCase
{
    public function testAiControllersDoNotReadEnvironmentDirectly(): void
    {
        $files = [
            'Application/Controllers/SysAdmin/AiViewController.php',
            'Application/Controllers/Api/AI/TelegramLinkController.php',
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
