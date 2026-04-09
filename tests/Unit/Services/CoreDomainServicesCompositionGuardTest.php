<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;

class CoreDomainServicesCompositionGuardTest extends TestCase
{
    public function testCoreDomainServicesDoNotInstantiateInsightsInline(): void
    {
        $orcamentoService = (string) file_get_contents('Application/Services/Orcamentos/OrcamentoService.php');

        $this->assertDoesNotMatchRegularExpression(
            '/new\s+OrcamentoInsightService\s*\(/',
            $orcamentoService,
            'OrcamentoService não deve instanciar OrcamentoInsightService diretamente.'
        );
    }
}
