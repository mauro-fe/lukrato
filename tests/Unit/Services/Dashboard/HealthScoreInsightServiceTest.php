<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Dashboard;

use Application\Repositories\LancamentoRepository;
use Application\Repositories\MetaRepository;
use Application\Repositories\OrcamentoRepository;
use Application\Services\Dashboard\HealthScoreInsightService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class HealthScoreInsightServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private LancamentoRepository $lancamentoRepo;
    private MetaRepository $metaRepo;
    private OrcamentoRepository $orcamentoRepo;
    private HealthScoreInsightService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->lancamentoRepo = Mockery::mock(LancamentoRepository::class);
        $this->metaRepo = Mockery::mock(MetaRepository::class);
        $this->orcamentoRepo = Mockery::mock(OrcamentoRepository::class);

        $this->service = new HealthScoreInsightService(
            $this->lancamentoRepo,
            $this->metaRepo,
            $this->orcamentoRepo
        );
    }

    public function testGenerateBuildsAllExpectedInsightsForLowFinancialHealth(): void
    {
        $this->lancamentoRepo
            ->shouldReceive('getResumoMes')
            ->once()
            ->with(12, '2026-03')
            ->andReturn([
                'saldo_atual' => -150.0,
                'count' => 3,
                'categories' => 2,
            ]);

        $this->metaRepo
            ->shouldReceive('countAtivas')
            ->once()
            ->with(12)
            ->andReturn(0);

        $this->orcamentoRepo
            ->shouldReceive('findByUserAndMonth')
            ->once()
            ->with(12, 3, 2026)
            ->andReturn(new EloquentCollection());

        $result = $this->service->generate(12, '2026-03');

        $this->assertSame(
            ['negative_balance', 'low_activity', 'low_categories', 'no_goals'],
            array_column($result, 'type'),
        );
        $this->assertSame(
            ['critical', 'high', 'medium', 'medium'],
            array_column($result, 'priority'),
        );
    }

    public function testGenerateReturnsEmptyArrayWhenFinancialHealthHasNoFlags(): void
    {
        $this->lancamentoRepo
            ->shouldReceive('getResumoMes')
            ->once()
            ->with(13, '2026-03')
            ->andReturn([
                'saldo_atual' => 500.0,
                'count' => 12,
                'categories' => 5,
            ]);

        $this->metaRepo
            ->shouldReceive('countAtivas')
            ->once()
            ->with(13)
            ->andReturn(2);

        $this->orcamentoRepo
            ->shouldReceive('findByUserAndMonth')
            ->once()
            ->with(13, 3, 2026)
            ->andReturn(new EloquentCollection());

        $this->assertSame(
            ['no_budgets'],
            array_column($this->service->generate(13, '2026-03'), 'type')
        );
    }
}


