<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Fatura;

use PHPUnit\Framework\TestCase;

class FaturaServicesCompositionGuardTest extends TestCase
{
    public function testModernizedFaturaServicesDoNotInstantiateDependenciesInlineInConstructors(): void
    {
        $files = [
            'Application/Services/Fatura/FaturaService.php',
            'Application/Services/Fatura/FaturaFormatterService.php',
            'Application/Services/Fatura/FaturaReadService.php',
            'Application/Services/Fatura/FaturaItemPaymentService.php',
            'Application/Services/Fatura/FaturaCreationService.php',
        ];

        foreach ($files as $filePath) {
            $content = (string) file_get_contents($filePath);

            $this->assertDoesNotMatchRegularExpression(
                '/function\s+__construct\s*\((?:(?!\)\s*\{).)*=\s*new\s+[\\\w]+/s',
                $content,
                "Construtor não deve usar default inline com new: {$filePath}"
            );

            $this->assertDoesNotMatchRegularExpression(
                '/function\s+__construct\s*\([^)]*\)\s*\{[\s\S]*?\?\?=?\s*new\s+[\\\w]+/s',
                $content,
                "Construtor não deve montar dependência inline com new: {$filePath}"
            );
        }
    }
}
