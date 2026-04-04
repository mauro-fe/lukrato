<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Report;

use Application\Services\Report\ReportService;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class ReportServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testGetCardDetailedReportCastsPreviousInvoiceAggregateBeforeHealthCalculation(): void
    {
        $service = new ReportService();

        $cartao = (object) [
            'id' => 22,
            'nome_cartao' => 'Cartao principal',
            'bandeira' => 'visa',
            'limite_total' => 1000.0,
            'limite_utilizado' => 0.0,
            'limite_disponivel_real' => 1000.0,
            'dia_vencimento' => 10,
            'conta' => null,
        ];

        $cartaoQuery = Mockery::mock();
        $cartaoQuery->shouldReceive('where')->once()->with('user_id', 10)->andReturnSelf();
        $cartaoQuery->shouldReceive('first')->once()->andReturn($cartao);

        $cartaoModel = Mockery::mock('alias:Application\\Models\\CartaoCredito');
        $cartaoModel->shouldReceive('where')->once()->with('id', 22)->andReturn($cartaoQuery);

        $itensFatura = new Collection();
        $parcelamentos = new Collection();

        $itensFaturaQuery = Mockery::mock();
        $itensFaturaQuery->shouldReceive('where')->once()->with('cartao_credito_id', 22)->andReturnSelf();
        $itensFaturaQuery->shouldReceive('where')->once()->with('mes_referencia', 3)->andReturnSelf();
        $itensFaturaQuery->shouldReceive('where')->once()->with('ano_referencia', 2026)->andReturnSelf();
        $itensFaturaQuery->shouldReceive('with')->once()->with('categoria')->andReturnSelf();
        $itensFaturaQuery->shouldReceive('orderBy')->once()->with('data_compra', 'desc')->andReturnSelf();
        $itensFaturaQuery->shouldReceive('get')->once()->andReturn($itensFatura);

        $parcelamentosQuery = Mockery::mock();
        $parcelamentosQuery->shouldReceive('where')->once()->with('cartao_credito_id', 22)->andReturnSelf();
        $parcelamentosQuery->shouldReceive('where')->once()->with('total_parcelas', '>', 1)->andReturnSelf();
        $parcelamentosQuery->shouldReceive('where')->once()->with('pago', false)->andReturnSelf();
        $parcelamentosQuery->shouldReceive('with')->once()->with('categoria')->andReturnSelf();
        $parcelamentosQuery->shouldReceive('orderBy')->once()->with('data_compra', 'asc')->andReturnSelf();
        $parcelamentosQuery->shouldReceive('get')->once()->andReturn($parcelamentos);

        $makeSumQuery = static function (int $expectedMonth, int $expectedYear, string $result): object {
            $query = Mockery::mock();
            $query->shouldReceive('where')->once()->with('cartao_credito_id', 22)->andReturnSelf();
            $query->shouldReceive('where')->once()->with('mes_referencia', $expectedMonth)->andReturnSelf();
            $query->shouldReceive('where')->once()->with('ano_referencia', $expectedYear)->andReturnSelf();
            $query->shouldReceive('sum')->once()->with('valor')->andReturn($result);

            return $query;
        };

        $faturaItemModel = Mockery::mock('alias:Application\\Models\\FaturaCartaoItem');
        $faturaItemModel->shouldReceive('where')->times(15)->with('user_id', 10)->andReturn(
            $itensFaturaQuery,
            $makeSumQuery(10, 2025, '0'),
            $makeSumQuery(11, 2025, '0'),
            $makeSumQuery(12, 2025, '0'),
            $makeSumQuery(1, 2026, '0'),
            $makeSumQuery(2, 2026, '0'),
            $makeSumQuery(3, 2026, '0'),
            $makeSumQuery(2, 2026, '313.63'),
            $parcelamentosQuery,
            $makeSumQuery(4, 2026, '0'),
            $makeSumQuery(5, 2026, '0'),
            $makeSumQuery(6, 2026, '0'),
            $makeSumQuery(7, 2026, '0'),
            $makeSumQuery(8, 2026, '0'),
            $makeSumQuery(9, 2026, '0'),
        );

        $result = $service->getCardDetailedReport(10, 22, '03', '2026');

        $this->assertSame(313.63, $result['fatura_mes']['fatura_anterior']);
        $this->assertSame('saudavel', $result['cartao']['status_saude']['status']);
        $this->assertStringContainsString('100%', $result['cartao']['status_saude']['comparacao']);
    }
}
