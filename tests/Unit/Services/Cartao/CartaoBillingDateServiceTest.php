<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Cartao;

use Application\Services\Cartao\CartaoBillingDateService;
use PHPUnit\Framework\TestCase;

class CartaoBillingDateServiceTest extends TestCase
{
    public function testCalcularCompetenciaAvancaParaProximoCicloQuandoCompraAconteceAposFechamento(): void
    {
        $service = new CartaoBillingDateService();

        $resultado = $service->calcularCompetencia('2026-04-07', 1);

        $this->assertSame([
            'mes' => 5,
            'ano' => 2026,
        ], $resultado);
    }

    public function testCalcularCompetenciaMantemMesmoCicloQuandoCompraAconteceAntesDoFechamento(): void
    {
        $service = new CartaoBillingDateService();

        $resultado = $service->calcularCompetencia('2026-04-03', 5);

        $this->assertSame([
            'mes' => 4,
            'ano' => 2026,
        ], $resultado);
    }

    public function testCalcularDataVencimentoJogaCompraParaProximaFaturaQuandoJaPassouDoFechamento(): void
    {
        $service = new CartaoBillingDateService();

        $resultado = $service->calcularDataVencimento('2026-04-07', 10, 1);

        $this->assertSame([
            'data' => '2026-05-10',
            'mes' => 5,
            'ano' => 2026,
        ], $resultado);
    }
}
