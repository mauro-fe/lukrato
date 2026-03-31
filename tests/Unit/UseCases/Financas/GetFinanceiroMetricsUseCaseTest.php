<?php

declare(strict_types=1);

namespace Tests\Unit\UseCases\Financas;

use Application\Repositories\LancamentoRepository;
use Application\UseCases\Financas\GetFinanceiroMetricsUseCase;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class GetFinanceiroMetricsUseCaseTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testExecuteReturnsCompetenciaMetricsWhenViewIsCompetencia(): void
    {
        $repo = Mockery::mock(LancamentoRepository::class);
        $repo->shouldReceive('sumReceitasCompetencia')->once()->with(10, '2026-03-01', '2026-03-31')->andReturn(2000.50);
        $repo->shouldReceive('sumDespesasCompetencia')->once()->with(10, '2026-03-01', '2026-03-31')->andReturn(850.25);
        $repo->shouldReceive('sumDespesasBrutasCompetencia')->once()->with(10, '2026-03-01', '2026-03-31')->andReturn(900.00);
        $repo->shouldReceive('sumUsoMetasDespesaCompetencia')->once()->with(10, '2026-03-01', '2026-03-31')->andReturn(120.00);
        $repo->shouldReceive('sumSaldoAcumuladoAte')->once()->with(10, '2026-03-31')->andReturn(5432.10);

        $useCase = new GetFinanceiroMetricsUseCase($repo);
        $result = $useCase->execute(10, '2026-03-01', '2026-03-31', 'competencia');

        $this->assertFalse($result->isError());
        $this->assertSame(200, $result->httpCode);
        $this->assertSame('competencia', $result->data['view'] ?? null);
        $this->assertSame(2000.50, $result->data['receitas'] ?? null);
        $this->assertSame(850.25, $result->data['despesas'] ?? null);
        $this->assertSame(1150.25, $result->data['resultado'] ?? null);
        $this->assertSame(5432.10, $result->data['saldo'] ?? null);
        $this->assertSame(5432.10, $result->data['saldoAcumulado'] ?? null);
    }

    public function testExecuteFallsBackToCaixaWhenViewIsInvalid(): void
    {
        $repo = Mockery::mock(LancamentoRepository::class);
        $repo->shouldReceive('sumReceitasCaixa')->once()->with(9, '2026-02-01', '2026-02-28')->andReturn(1000.00);
        $repo->shouldReceive('sumDespesasCaixa')->once()->with(9, '2026-02-01', '2026-02-28')->andReturn(450.00);
        $repo->shouldReceive('sumDespesasBrutasCaixa')->once()->with(9, '2026-02-01', '2026-02-28')->andReturn(470.00);
        $repo->shouldReceive('sumUsoMetasDespesaCaixa')->once()->with(9, '2026-02-01', '2026-02-28')->andReturn(80.00);
        $repo->shouldReceive('sumSaldoAcumuladoAte')->once()->with(9, '2026-02-28')->andReturn(3200.00);

        $useCase = new GetFinanceiroMetricsUseCase($repo);
        $result = $useCase->execute(9, '2026-02-01', '2026-02-28', 'qualquer');

        $this->assertFalse($result->isError());
        $this->assertSame('caixa', $result->data['view'] ?? null);
        $this->assertSame(550.00, $result->data['resultado'] ?? null);
        $this->assertSame(470.00, $result->data['despesas_brutas'] ?? null);
        $this->assertSame(80.00, $result->data['uso_metas'] ?? null);
    }
}
