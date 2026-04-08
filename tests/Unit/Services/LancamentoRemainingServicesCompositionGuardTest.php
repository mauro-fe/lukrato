<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;

class LancamentoRemainingServicesCompositionGuardTest extends TestCase
{
    public function testRemainingLancamentoServicesDoNotInstantiateDependenciesInlineInConstructors(): void
    {
        $files = [
            'Application/Services/Lancamento/LancamentoCreationService.php',
            'Application/Services/Lancamento/LancamentoRecurrenceService.php',
            'Application/Services/Lancamento/LancamentoUpdateService.php',
            'Application/Services/Lancamento/LancamentoExportService.php',
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

            $this->assertDoesNotMatchRegularExpression(
                '/function\s+__construct\s*\([^)]*\)\s*\{[\s\S]*?resolveOrNew\s*\([\s\S]*?fn\s*\(\)\s*:\s*[\\\w]+\s*=>\s*new\s+[\\\w]+/s',
                $content,
                "Construtor não deve montar dependência inline com factory manual: {$filePath}"
            );
        }
    }
}
