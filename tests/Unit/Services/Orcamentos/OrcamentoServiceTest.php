<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Orcamentos;

use Application\Repositories\OrcamentoRepository;
use Application\Services\Orcamentos\OrcamentoService;
use Application\Services\Plan\PlanLimitService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class OrcamentoServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testResumoAggregatesTotalsAndFinancialHealth(): void
    {
        $service = $this->makeServiceWithBudgets([
            [
                'categoria_id' => 1,
                'categoria_nome' => 'Mercado',
                'valor_limite' => 100.0,
                'gasto_real' => 40.0,
                'disponivel' => 60.0,
                'percentual' => 40.0,
                'status' => 'ok',
            ],
            [
                'categoria_id' => 2,
                'categoria_nome' => 'Lazer',
                'valor_limite' => 200.0,
                'gasto_real' => 180.0,
                'disponivel' => 20.0,
                'percentual' => 90.0,
                'status' => 'alerta',
            ],
            [
                'categoria_id' => 3,
                'categoria_nome' => 'Transporte',
                'valor_limite' => 300.0,
                'gasto_real' => 360.0,
                'disponivel' => 0.0,
                'percentual' => 120.0,
                'status' => 'estourado',
            ],
        ]);

        $summary = $service->resumo(77, 3, 2026);

        $this->assertSame(3, $summary['total_categorias']);
        $this->assertSame(600.0, $summary['total_limite']);
        $this->assertSame(580.0, $summary['total_gasto']);
        $this->assertSame(80.0, $summary['total_disponivel']);
        $this->assertSame(96.7, $summary['percentual_geral']);
        $this->assertSame(1, $summary['em_alerta']);
        $this->assertSame(1, $summary['estourados']);
        $this->assertSame(
            ['score' => 47.0, 'label' => 'Atenção', 'cor' => '#f59e0b'],
            $summary['saude_financeira']
        );
    }

    public function testGetInsightsSortsByPriorityAndLimitsResultCount(): void
    {
        $budgets = [
            [
                'categoria_id' => 1,
                'categoria_nome' => 'Mercado',
                'valor_limite' => 200.0,
                'gasto_real' => 260.0,
                'disponivel' => 0.0,
                'excedido' => 60.0,
                'percentual' => 130.0,
                'status' => 'estourado',
            ],
            [
                'categoria_id' => 2,
                'categoria_nome' => 'Lazer',
                'valor_limite' => 250.0,
                'gasto_real' => 220.0,
                'disponivel' => 30.0,
                'excedido' => 0.0,
                'percentual' => 88.0,
                'status' => 'alerta',
            ],
            [
                'categoria_id' => 3,
                'categoria_nome' => 'Saúde',
                'valor_limite' => 180.0,
                'gasto_real' => 50.0,
                'disponivel' => 130.0,
                'excedido' => 0.0,
                'percentual' => 27.8,
                'status' => 'ok',
            ],
            [
                'categoria_id' => 4,
                'categoria_nome' => 'Educação',
                'valor_limite' => 300.0,
                'gasto_real' => 75.0,
                'disponivel' => 225.0,
                'excedido' => 0.0,
                'percentual' => 25.0,
                'status' => 'ok',
            ],
        ];

        $repo = Mockery::mock(OrcamentoRepository::class);
        $repo->shouldReceive('getGastoRealComFallback')
            ->times(4)
            ->andReturnUsing(function (int $userId, int $categoriaId, int $mes, int $ano): float {
                return match ($categoriaId) {
                    1 => 200.0,
                    2 => 100.0,
                    3 => 100.0,
                    4 => 20.0,
                    default => 0.0,
                };
            });

        $service = $this->makeServiceWithBudgets($budgets, $repo);

        $insights = $service->getInsights(77, 3, 2026);

        $this->assertCount(8, $insights);
        $this->assertSame('perigo', $insights[0]['tipo']);
        $this->assertSame('alerta', $insights[1]['tipo']);

        $types = array_column($insights, 'tipo');
        $firstInfo = array_search('info', $types, true);
        $firstPositive = array_search('positivo', $types, true);

        $this->assertNotFalse($firstInfo);
        $this->assertNotFalse($firstPositive);
        $this->assertLessThan($firstPositive, $firstInfo);
        $this->assertStringContainsString('estourou o orçamento', $insights[0]['titulo']);
    }

    public function testPrivateStatusAndHealthRulesRespectBoundaries(): void
    {
        $service = new OrcamentoService();

        $this->assertSame('ok', $this->invokePrivate($service, 'getStatusOrcamento', [49.9]));
        $this->assertSame('atencao', $this->invokePrivate($service, 'getStatusOrcamento', [50.0]));
        $this->assertSame('alerta', $this->invokePrivate($service, 'getStatusOrcamento', [80.0]));
        $this->assertSame('estourado', $this->invokePrivate($service, 'getStatusOrcamento', [100.0]));

        $emptyHealth = $this->invokePrivate($service, 'calcularSaudeFinanceira', [[]]);
        $this->assertSame(['score' => 100, 'label' => 'Excelente', 'cor' => '#10b981'], $emptyHealth);
    }

    public function testSalvarMultiplosDeduplicatesCategoriasForPlanLimitAndUpsert(): void
    {
        $repo = Mockery::mock(OrcamentoRepository::class);
        $planLimit = Mockery::mock(PlanLimitService::class);

        $repo->shouldReceive('getExistingCategoriaIdsForMonth')
            ->once()
            ->withArgs(function (int $userId, int $mes, int $ano, array $categoriaIds): bool {
                sort($categoriaIds);

                return $userId === 77
                    && $mes === 4
                    && $ano === 2026
                    && $categoriaIds === [1, 2];
            })
            ->andReturn([]);

        $planLimit->shouldReceive('canCreateOrcamento')
            ->once()
            ->with(77)
            ->andReturn([
                'allowed' => true,
                'limit' => 2,
                'remaining' => 2,
                'message' => '',
            ]);

        $repo->shouldReceive('upsert')
            ->once()
            ->with(77, 1, 4, 2026, [
                'valor_limite' => 150.0,
                'rollover' => true,
                'alerta_80' => false,
                'alerta_100' => true,
            ]);

        $repo->shouldReceive('upsert')
            ->once()
            ->with(77, 2, 4, 2026, [
                'valor_limite' => 200.0,
                'rollover' => false,
                'alerta_80' => true,
                'alerta_100' => false,
            ]);

        $service = new class($repo, $planLimit) extends OrcamentoService {
            public function __construct(OrcamentoRepository $repo, PlanLimitService $planLimit)
            {
                parent::__construct($repo, $planLimit);
            }

            public function listarComProgresso(int $userId, int $mes, int $ano): array
            {
                return [['id' => 99, 'mes' => $mes, 'ano' => $ano]];
            }
        };

        $result = $service->salvarMultiplos(77, 4, 2026, [
            ['categoria_id' => 1, 'valor_limite' => 100.0],
            ['categoria_id' => 1, 'valor_limite' => 150.0, 'rollover' => true, 'alerta_80' => false],
            ['categoria_id' => 2, 'valor_limite' => 200.0, 'alerta_100' => false],
        ]);

        $this->assertSame([['id' => 99, 'mes' => 4, 'ano' => 2026]], $result);
    }

    private function makeServiceWithBudgets(array $budgets, ?OrcamentoRepository $repo = null): OrcamentoService
    {
        $service = new class($budgets) extends OrcamentoService {
            public function __construct(private array $budgets)
            {
                parent::__construct();
            }

            public function listarComProgresso(int $userId, int $mes, int $ano): array
            {
                return $this->budgets;
            }
        };

        if ($repo !== null) {
            $this->setPrivateProperty($service, 'repo', $repo);
        }

        return $service;
    }

    private function setPrivateProperty(object $object, string $property, mixed $value): void
    {
        $setter = \Closure::bind(function (string $property, mixed $value): void {
            $this->{$property} = $value;
        }, $object, OrcamentoService::class);

        $setter($property, $value);
    }

    private function invokePrivate(object $object, string $method, array $arguments = []): mixed
    {
        $caller = \Closure::bind(
            fn (...$args) => $this->{$method}(...$args),
            $object,
            $object
        );

        return $caller(...$arguments);
    }
}


