<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Cartao;

use Application\Services\Cartao\CartaoFaturaReadService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class CartaoFaturaReadServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testVerificarVencimentosProximosUsesDueMonthToSumInvoiceTotal(): void
    {
        $today = new \DateTimeImmutable();
        $expectedMonth = (int) $today->format('n');
        $expectedYear = (int) $today->format('Y');
        $expectedDueDay = min(((int) $today->format('j')) + 1, (int) $today->format('t'));

        $card = (object) [
            'id' => 77,
            'nome_cartao' => 'Nubank',
            'dia_vencimento' => $expectedDueDay,
        ];

        $cardActiveQuery = Mockery::mock();
        $cardBaseQuery = Mockery::mock();
        $cardBaseQuery->shouldReceive('where')->once()->with('ativo', true)->andReturn($cardActiveQuery);
        $cardActiveQuery->shouldReceive('get')->once()->andReturn(new EloquentCollection([$card]));

        $cardModel = Mockery::mock('alias:Application\Models\CartaoCredito');
        $cardModel->shouldReceive('where')->once()->with('user_id', 321)->andReturn($cardBaseQuery);

        $invoiceQuery = Mockery::mock();
        $invoiceQuery->shouldReceive('where')->once()->with('pago', false)->andReturnSelf();
        $invoiceQuery->shouldReceive('whereYear')->once()->with('data_vencimento', $expectedYear)->andReturnSelf();
        $invoiceQuery->shouldReceive('whereMonth')->once()->with('data_vencimento', $expectedMonth)->andReturnSelf();
        $invoiceQuery->shouldReceive('whereNull')->once()->with('cancelado_em')->andReturnSelf();
        $invoiceQuery->shouldReceive('sum')->once()->with('valor')->andReturn(258.34);

        $invoiceModel = Mockery::mock('alias:Application\Models\FaturaCartaoItem');
        $invoiceModel->shouldReceive('where')->once()->with('cartao_credito_id', 77)->andReturn($invoiceQuery);

        $service = new CartaoFaturaReadService();

        $result = $service->verificarVencimentosProximos(321, 7);

        $this->assertCount(1, $result);
        $this->assertSame(77, $result[0]['cartao_id']);
        $this->assertSame(258.34, $result[0]['valor_fatura']);
        $this->assertSame($expectedMonth, $result[0]['mes']);
        $this->assertSame($expectedYear, $result[0]['ano']);
    }
}
