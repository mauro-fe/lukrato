<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Importacao;

use PHPUnit\Framework\TestCase;

class ImportacaoEnvBoundaryTest extends TestCase
{
    public function testImportacaoServicesDoNotReadEnvironmentDirectly(): void
    {
        $workspaceRoot = dirname(__DIR__, 4);
        $servicesPath = $workspaceRoot . '/Application/Services/Importacao';
        $violations = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($servicesPath, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo || !$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $contents = file_get_contents($file->getPathname());
            if ($contents === false) {
                $this->fail(sprintf('Nao foi possivel ler %s', $file->getPathname()));
            }

            if (preg_match('/\$_ENV|getenv\s*\(/i', $contents) === 1) {
                $violations[] = str_replace('\\', '/', substr($file->getPathname(), strlen($workspaceRoot) + 1));
            }
        }

        $this->assertSame(
            [],
            $violations,
            'Importacao services devem acessar ambiente apenas pela configuracao de runtime.'
        );
    }
}
