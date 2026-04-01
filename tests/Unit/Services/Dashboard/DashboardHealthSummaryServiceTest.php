<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Dashboard;

use Application\Services\Dashboard\DashboardHealthSummaryService;
use Application\Services\Dashboard\HealthScoreInsightService;
use Application\Services\Dashboard\HealthScoreService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class DashboardHealthSummaryServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testGenerateSortsInsightsBuildsRecommendationsAndResolvesStatus(): void
    {
        $healthScoreService = Mockery::mock(HealthScoreService::class);
        $healthScoreInsightService = Mockery::mock(HealthScoreInsightService::class);

        $healthScoreService
            ->shouldReceive('calculateUserHealthScore')
            ->once()
            ->with(21, '2026-03')
            ->andReturn([
                'score' => 35,
                'savingsRate' => 5,
            ]);

        $healthScoreInsightService
            ->shouldReceive('generate')
            ->once()
            ->with(21, '2026-03')
            ->andReturn([
                ['type' => 'low_categories', 'priority' => 'medium', 'message' => 'Poucas categorias'],
                ['type' => 'negative_balance', 'priority' => 'critical', 'message' => 'Saldo negativo'],
                ['type' => 'low_activity', 'priority' => 'high', 'message' => 'Pouca atividade'],
                ['type' => 'negative_balance', 'priority' => 'critical', 'message' => 'Saldo negativo duplicado'],
                ['type' => 'no_goals', 'priority' => 'medium', 'message' => 'Sem metas'],
            ]);

        $service = new DashboardHealthSummaryService($healthScoreService, $healthScoreInsightService);

        $result = $service->generate(21, '2026-03');

        $this->assertSame('critico', $result['status']['label']);
        $this->assertSame('danger', $result['status']['color']);
        $this->assertSame(
            ['negative_balance', 'negative_balance', 'low_activity', 'low_categories', 'no_goals'],
            array_column($result['insights'], 'type'),
        );
        $this->assertSame([
            'Reduza despesas imediatamente',
            'Registre mais transações para melhor controle',
            'Organize seus gastos em mais categorias',
            'Defina metas financeiras',
        ], $result['recommendations']);
    }
}


