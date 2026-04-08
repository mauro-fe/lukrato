<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Cartao;

use PHPUnit\Framework\TestCase;

class CartaoServicesCompositionGuardTest extends TestCase
{
    public function testModernizedCartaoServicesDoNotInstantiateDependenciesInlineInConstructors(): void
    {
        $files = [
            'Application/Services/Cartao/CartaoCreditoLancamentoService.php',
            'Application/Services/Cartao/CartaoApiWorkflowService.php',
            'Application/Services/Cartao/CartaoCreditoService.php',
            'Application/Services/Cartao/CartaoFaturaPaymentService.php',
            'Application/Services/Cartao/CartaoFaturaService.php',
            'Application/Services/Cartao/RecorrenciaCartaoService.php',
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
