<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Dashboard;

use Application\Models\Meta;
use Application\Repositories\LancamentoRepository;
use Application\Repositories\MetaRepository;
use Application\Repositories\OrcamentoRepository;
use Application\Services\Dashboard\HealthScoreService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use InvalidArgumentException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class HealthScoreServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private LancamentoRepository $lancamentoRepo;
    private OrcamentoRepository $orcamentoRepo;
    private MetaRepository $metaRepo;
    private HealthScoreService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->lancamentoRepo = Mockery::mock(LancamentoRepository::class);
        $this->orcamentoRepo = Mockery::mock(OrcamentoRepository::class);
        $this->metaRepo = Mockery::mock(MetaRepository::class);

        $this->service = new HealthScoreService(
            $this->lancamentoRepo,
            $this->orcamentoRepo,
            $this->metaRepo,
        );
    }

    public function testCalculateUserHealthScoreBuildsExpectedCompositePayload(): void
    {
        $userId = 42;
        $month = '2026-03';

        $this->lancamentoRepo
            ->shouldReceive('getResumoMes')
            ->once()
            ->with($userId, $month)
            ->andReturn([
                'receitas' => 1000.0,
                'despesas' => 700.0,
                'count' => 20,
                'categories' => 8,
                'saldo_atual' => 1500.0,
            ]);

        $this->orcamentoRepo
            ->shouldReceive('findByUserAndMonth')
            ->once()
            ->with($userId, 3, 2026)
            ->andReturn(new EloquentCollection([
                (object) ['categoria_id' => 1, 'valor_limite' => 500.0],
                (object) ['categoria_id' => 2, 'valor_limite' => 300.0],
            ]));

        $this->lancamentoRepo
            ->shouldReceive('getSomaGastosPorCategoria')
            ->once()
            ->with($userId, 3, 2026)
            ->andReturn([
                1 => 450.0,
                2 => 450.0,
            ]);

        $this->metaRepo
            ->shouldReceive('findByUser')
            ->once()
            ->with($userId, Meta::STATUS_ATIVA)
            ->andReturn(new EloquentCollection([
                (object) ['valor_atual' => 1000.0, 'valor_alvo' => 1000.0],
                (object) ['valor_atual' => 250.0, 'valor_alvo' => 500.0],
            ]));

        $result = $this->service->calculateUserHealthScore($userId, $month);

        $this->assertSame([
            'score' => 100,
            'savingsRate' => 30,
            'consistency' => 'Excelente',
            'categories' => 8,
            'lancamentos' => 20,
            'orcamentos' => 2,
            'orcamentos_ok' => 1,
            'metas_ativas' => 2,
            'metas_concluidas' => 1,
        ], $result);
    }

    public function testCalculateUserHealthScoreDefaultsMissingCategorySpendToZero(): void
    {
        $userId = 7;
        $month = '2026-04';

        $this->lancamentoRepo
            ->shouldReceive('getResumoMes')
            ->once()
            ->with($userId, $month)
            ->andReturn([
                'receitas' => 500.0,
                'despesas' => 500.0,
                'count' => 4,
                'categories' => 2,
                'saldo_atual' => 0.0,
            ]);

        $this->orcamentoRepo
            ->shouldReceive('findByUserAndMonth')
            ->once()
            ->with($userId, 4, 2026)
            ->andReturn(new EloquentCollection([
                (object) ['categoria_id' => 10, 'valor_limite' => 100.0],
            ]));

        $this->lancamentoRepo
            ->shouldReceive('getSomaGastosPorCategoria')
            ->once()
            ->with($userId, 4, 2026)
            ->andReturn([]);

        $this->metaRepo
            ->shouldReceive('findByUser')
            ->once()
            ->with($userId, Meta::STATUS_ATIVA)
            ->andReturn(new EloquentCollection());

        $result = $this->service->calculateUserHealthScore($userId, $month);

        $this->assertSame(1, $result['orcamentos']);
        $this->assertSame(1, $result['orcamentos_ok']);
        $this->assertSame('Regular', $result['consistency']);
        $this->assertSame(0, $result['metas_ativas']);
    }

    public function testCalculateUserHealthScoreRejectsInvalidMonthFormat(): void
    {
        $userId = 99;
        $invalidMonth = '2026-13';

        $this->lancamentoRepo
            ->shouldReceive('getResumoMes')
            ->once()
            ->with($userId, $invalidMonth)
            ->andReturn([
                'receitas' => 0.0,
                'despesas' => 0.0,
                'count' => 0,
                'categories' => 0,
                'saldo_atual' => 0.0,
            ]);

        $this->orcamentoRepo->shouldNotReceive('findByUserAndMonth');
        $this->metaRepo->shouldNotReceive('findByUser');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Formato de mes invalido');

        $this->service->calculateUserHealthScore($userId, $invalidMonth);
    }
}


