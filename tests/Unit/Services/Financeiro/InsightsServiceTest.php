<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Financeiro;

use Application\DTO\InsightItemDTO;
use Application\Enums\InsightType;
use Application\Services\Financeiro\InsightsService;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class InsightsServiceTest extends TestCase
{
    private InsightsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new InsightsService();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function testAddDespesasComparisonAddsWarningForRelevantIncrease(): void
    {
        $this->setState([
            'currentDespesas' => 150.0,
            'previousDespesas' => 100.0,
        ]);

        $insights = [];
        $this->invokeInsightMutator('addDespesasComparison', $insights);

        $this->assertCount(1, $insights);
        $this->assertSame(InsightType::WARNING, $insights[0]->type);
        $this->assertSame('Despesas aumentaram', $insights[0]->title);
        $this->assertSame(50.0, $insights[0]->percentage);
    }

    public function testAddSaldoAnalysisCoversNegativeBalanceAndStrongSavings(): void
    {
        $this->setState([
            'currentReceitas' => 100.0,
            'currentDespesas' => 180.0,
        ]);

        $negativeInsights = [];
        $this->invokeInsightMutator('addSaldoAnalysis', $negativeInsights);

        $this->assertCount(1, $negativeInsights);
        $this->assertSame(InsightType::DANGER, $negativeInsights[0]->type);
        $this->assertSame('Saldo negativo', $negativeInsights[0]->title);
        $this->assertSame(80.0, $negativeInsights[0]->value);

        $this->setState([
            'currentReceitas' => 1000.0,
            'currentDespesas' => 600.0,
        ]);

        $positiveInsights = [];
        $this->invokeInsightMutator('addSaldoAnalysis', $positiveInsights);

        $this->assertCount(1, $positiveInsights);
        $this->assertSame(InsightType::SUCCESS, $positiveInsights[0]->type);
        $this->assertSame('Ótima economia!', $positiveInsights[0]->title);
        $this->assertSame(40.0, $positiveInsights[0]->percentage);
    }

    public function testAddReceitasVariationAddsWarningWhenRevenueDrops(): void
    {
        $this->setState([
            'currentReceitas' => 600.0,
            'previousReceitas' => 1000.0,
        ]);

        $insights = [];
        $this->invokeInsightMutator('addReceitasVariation', $insights);

        $this->assertCount(1, $insights);
        $this->assertSame(InsightType::WARNING, $insights[0]->type);
        $this->assertSame('Receitas diminuíram', $insights[0]->title);
        $this->assertSame(40.0, $insights[0]->percentage);
    }

    public function testAddDailyAverageProjectionWarnsWhenProjectedSpendingExceedsIncome(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 15, 10, 0, 0));

        $this->setState([
            'currentEnd' => Carbon::create(2026, 3, 31)->endOfMonth(),
            'currentReceitas' => 2000.0,
            'currentDespesas' => 1800.0,
        ]);

        $insights = [];
        $this->invokeInsightMutator('addDailyAverageProjection', $insights, 2026, 3);

        $this->assertCount(1, $insights);
        $this->assertSame(InsightType::WARNING, $insights[0]->type);
        $this->assertSame('Projeção mensal de gastos', $insights[0]->title);
        $this->assertSame(3720.0, $insights[0]->value);
        $this->assertStringContainsString('pode ultrapassar sua receita', $insights[0]->message);
    }

    public function testToArrayListSerializesInsightDtos(): void
    {
        $insights = [
            new InsightItemDTO(
                type: InsightType::INFO,
                icon: 'calculator',
                title: 'Projeção',
                message: 'Mensagem',
                value: 99.9,
                percentage: 12.5,
            ),
        ];

        $serialized = InsightsService::toArrayList($insights);

        $this->assertSame([
            [
                'type' => 'info',
                'icon' => 'calculator',
                'title' => 'Projeção',
                'message' => 'Mensagem',
                'value' => 99.9,
                'percentage' => 12.5,
            ],
        ], $serialized);
    }

    public function testGetPaymentMethodNameMapsFriendlyLabels(): void
    {
        $this->assertSame('Pix', $this->invokePrivate('getPaymentMethodName', ['pix']));
        $this->assertSame('Cartão de Crédito', $this->invokePrivate('getPaymentMethodName', ['cartao_credito']));
        $this->assertSame('Outro', $this->invokePrivate('getPaymentMethodName', ['outro']));
    }

    private function setState(array $properties): void
    {
        $setter = \Closure::bind(function (array $properties): void {
            foreach ($properties as $property => $value) {
                $this->{$property} = $value;
            }
        }, $this->service, $this->service);

        $setter($properties);
    }

    private function invokePrivate(string $method, array $arguments = []): mixed
    {
        $caller = \Closure::bind(
            fn (...$args) => $this->{$method}(...$args),
            $this->service,
            $this->service
        );

        return $caller(...$arguments);
    }

    private function invokeInsightMutator(string $method, array &$insights, mixed ...$arguments): void
    {
        $caller = \Closure::bind(function (string $method, array &$insights, mixed ...$arguments): void {
            $this->{$method}($insights, ...$arguments);
        }, $this->service, $this->service);

        $caller($method, $insights, ...$arguments);
    }
}
