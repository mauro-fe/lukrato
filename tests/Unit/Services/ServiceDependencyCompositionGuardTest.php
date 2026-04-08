<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;

class ServiceDependencyCompositionGuardTest extends TestCase
{
    public function testModernizedServicesDoNotInstantiateDependenciesInlineInConstructors(): void
    {
        $files = [
            'Application/Services/Orcamentos/OrcamentoService.php',
            'Application/Services/Metas/MetaService.php',
            'Application/Services/Conta/TransferenciaService.php',
            'Application/Services/Lancamento/LancamentoDeletionService.php',
            'Application/Services/Lancamento/LancamentoStatusService.php',
        ];

        foreach ($files as $filePath) {
            $content = (string) file_get_contents($filePath);

            $this->assertDoesNotMatchRegularExpression(
                '/function\s+__construct\s*\((?:(?!\)\s*\{).)*=\s*new\s+[\\\w]+/s',
                $content,
                "Construtor não deve usar default inline com new: {$filePath}"
            );

            $this->assertDoesNotMatchRegularExpression(
                '/function\s+__construct\s*\([^)]*\)\s*\{[\s\S]*?\?\?\s*new\s+[\\\w]+/s',
                $content,
                "Construtor não deve montar dependência inline com ?? new: {$filePath}"
            );
        }
    }
}
