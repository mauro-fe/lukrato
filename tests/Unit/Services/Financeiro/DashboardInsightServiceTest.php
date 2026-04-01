<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Financeiro;

use Application\Repositories\LancamentoRepository;
use Application\Services\Financeiro\DashboardInsightService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class DashboardInsightServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private LancamentoRepository $lancamentoRepo;
    private DashboardInsightService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->lancamentoRepo = Mockery::mock(LancamentoRepository::class);
        $this->service = new DashboardInsightService($this->lancamentoRepo);
    }

    public function testBuildComparativoCompetenciaCaixaResponseCalculatesNormalizedTotals(): void
    {
        $result = $this->service->buildComparativoCompetenciaCaixaResponse([
            'competencia' => [
                'receitas' => '1000.50',
                'despesas' => '700.25',
            ],
            'caixa' => [
                'receitas' => '900.00',
                'despesas' => '800.25',
            ],
        ], '2026-03');

        $this->assertSame('2026-03', $result['month']);
        $this->assertSame(1000.5, $result['competencia']['receitas']);
        $this->assertSame(300.25, $result['competencia']['resultado']);
        $this->assertSame(-100.0, $result['diferenca']['despesas']);
        $this->assertSame(200.5, $result['diferenca']['resultado']);
    }

    public function testGetRecentTransactionsReturnsEmptyArrayWithoutQueryWhenLimitIsZero(): void
    {
        $this->lancamentoRepo->shouldNotReceive('getRecentTransactions');

        $result = $this->service->getRecentTransactions(1, '2026-03-01', '2026-03-31', 0);

        $this->assertSame([], $result);
    }

    public function testGetRecentTransactionsMapsRepositoryRowsToApiPayload(): void
    {
        $rows = new EloquentCollection([
            (object) [
                'id' => '15',
                'data' => '2026-03-10',
                'tipo' => 'despesa',
                'valor' => '123.45',
                'descricao' => null,
                'categoria_id' => 0,
                'conta_id' => 8,
                'categoria' => 'Mercado',
                'conta' => 'Conta principal',
            ],
        ]);

        $this->lancamentoRepo
            ->shouldReceive('getRecentTransactions')
            ->once()
            ->with(1, '2026-03-01', '2026-03-31', 5)
            ->andReturn($rows);

        $result = $this->service->getRecentTransactions(1, '2026-03-01', '2026-03-31', 5);

        $this->assertSame([
            [
                'id' => 15,
                'data' => '2026-03-10',
                'tipo' => 'despesa',
                'valor' => 123.45,
                'descricao' => '',
                'categoria_id' => null,
                'conta_id' => 8,
                'categoria' => 'Mercado',
                'categoria_icone' => '',
                'conta' => 'Conta principal',
                'pago' => false,
            ],
        ], $result);
    }

    public function testGenerateGreetingInsightReturnsFallbackWhenThereAreNoSignals(): void
    {
        $this->lancamentoRepo
            ->shouldReceive('getResumoMes')
            ->once()
            ->with(7, '2026-03')
            ->andReturn([
                'receitas' => 0.0,
                'despesas' => 0.0,
                'count' => 0,
            ]);

        $this->lancamentoRepo
            ->shouldReceive('getResumoMes')
            ->once()
            ->with(7, '2026-02')
            ->andReturn([
                'receitas' => 0.0,
                'despesas' => 0.0,
                'count' => 0,
            ]);

        $result = $this->service->generateGreetingInsight(7, '2026-03', '2026-02');

        $this->assertSame('wallet', $result['icon']);
        $this->assertSame('var(--color-primary)', $result['color']);
        $this->assertStringContainsString('R$ 0,00', $result['message']);
    }

    public function testGenerateGreetingInsightReturnsExpenseReductionInsightWhenItIsTheOnlyMatch(): void
    {
        $this->lancamentoRepo
            ->shouldReceive('getResumoMes')
            ->once()
            ->with(9, '2026-03')
            ->andReturn([
                'receitas' => 800.0,
                'despesas' => 400.0,
                'count' => 4,
            ]);

        $this->lancamentoRepo
            ->shouldReceive('getResumoMes')
            ->once()
            ->with(9, '2026-02')
            ->andReturn([
                'receitas' => 1000.0,
                'despesas' => 500.0,
                'count' => 4,
            ]);

        $result = $this->service->generateGreetingInsight(9, '2026-03', '2026-02');

        $this->assertSame('zap', $result['icon']);
        $this->assertSame('#3b82f6', $result['color']);
        $this->assertStringContainsString('economizou', $result['message']);
    }
}
