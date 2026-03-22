<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Financeiro;

use Application\Repositories\LancamentoRepository;
use Application\Repositories\MetaRepository;
use Application\Services\Financeiro\HealthScoreInsightService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class HealthScoreInsightServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private LancamentoRepository $lancamentoRepo;
    private MetaRepository $metaRepo;
    private HealthScoreInsightService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->lancamentoRepo = Mockery::mock(LancamentoRepository::class);
        $this->metaRepo = Mockery::mock(MetaRepository::class);

        $this->service = new HealthScoreInsightService($this->lancamentoRepo, $this->metaRepo);
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

        $this->assertSame([], $this->service->generate(13, '2026-03'));
    }
}
