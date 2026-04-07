<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Fatura;

use Application\Services\Fatura\FaturaInstallmentCalculatorService;
use PHPUnit\Framework\TestCase;

class FaturaInstallmentCalculatorServiceTest extends TestCase
{
    public function testCalcularCompetenciaFaturaAvancaParaProximoCicloQuandoCompraAconteceAposFechamento(): void
    {
        $service = new FaturaInstallmentCalculatorService();

        $resultado = $service->calcularCompetenciaFatura(7, 4, 2026, 1);

        $this->assertSame([
            'mes' => 5,
            'ano' => 2026,
        ], $resultado);
    }
}
