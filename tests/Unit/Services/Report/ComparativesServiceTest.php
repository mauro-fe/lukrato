<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Report;

use Application\Models\Lancamento;
use Application\Services\Report\ComparativesService;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class ComparativesServiceTest extends TestCase
{
    private ComparativesService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ComparativesService();

        $this->setState([
            'currentMonthData' => (object) ['receitas' => 1500.0, 'despesas' => 900.0],
            'previousMonthData' => (object) ['receitas' => 1000.0, 'despesas' => 1000.0],
            'currentYearData' => (object) ['receitas' => 12000.0, 'despesas' => 8000.0],
            'previousYearData' => (object) ['receitas' => 10000.0, 'despesas' => 9000.0],
            'currentEnd' => Carbon::create(2026, 3, 31)->endOfDay(),
            'previousMonthEnd' => Carbon::create(2026, 2, 28)->endOfDay(),
        ]);
    }

    public function testBuildMonthlyComparisonUsesLoadedState(): void
    {
        $comparison = $this->invokePrivate('buildMonthlyComparison');

        $this->assertSame(
            [
                'receitas' => 1500.0,
                'despesas' => 900.0,
                'saldo' => 600.0,
            ],
            $comparison['current']
        );
        $this->assertSame(
            [
                'receitas' => 1000.0,
                'despesas' => 1000.0,
                'saldo' => 0.0,
            ],
            $comparison['previous']
        );
        $this->assertSame(50.0, $comparison['variation']['receitas']);
        $this->assertSame(-10.0, $comparison['variation']['despesas']);
        $this->assertSame(100.0, $comparison['variation']['saldo']);
    }

    public function testBuildYearlyComparisonCalculatesSaldoVariation(): void
    {
        $comparison = $this->invokePrivate('buildYearlyComparison');

        $this->assertSame(4000.0, $comparison['current']['saldo']);
        $this->assertSame(1000.0, $comparison['previous']['saldo']);
        $this->assertSame(20.0, $comparison['variation']['receitas']);
        $this->assertSame(-11.11, $comparison['variation']['despesas']);
        $this->assertSame(300.0, $comparison['variation']['saldo']);
    }

    public function testComparisonsCastDatabaseAggregateStringsBeforeVariation(): void
    {
        $this->setState([
            'currentMonthData' => (object) ['receitas' => '210.00', 'despesas' => '90.00'],
            'previousMonthData' => (object) ['receitas' => '105.00', 'despesas' => '120.00'],
            'currentYearData' => (object) ['receitas' => '1000.00', 'despesas' => '700.00'],
            'previousYearData' => (object) ['receitas' => '800.00', 'despesas' => '600.00'],
        ]);

        $monthly = $this->invokePrivate('buildMonthlyComparison');
        $yearly = $this->invokePrivate('buildYearlyComparison');

        $this->assertSame(210.0, $monthly['current']['receitas']);
        $this->assertSame(100.0, $monthly['variation']['receitas']);
        $this->assertSame(-25.0, $monthly['variation']['despesas']);
        $this->assertSame(25.0, $yearly['variation']['receitas']);
        $this->assertSame(50.0, $yearly['variation']['saldo']);
    }

    public function testBuildDailyAverageUsesMonthLengths(): void
    {
        $average = $this->invokePrivate('buildDailyAverage');

        $this->assertSame(29.03, $average['atual']);
        $this->assertSame(35.71, $average['anterior']);
        $this->assertSame(-18.71, $average['variacao']);
    }

    public function testBuildSavingsRateAndZeroBaseVariationAreSafe(): void
    {
        $rate = $this->invokePrivate('buildSavingsRate');

        $this->assertSame(40.0, $rate['atual']);
        $this->assertSame(0.0, $rate['anterior']);
        $this->assertSame(40.0, $rate['diferenca']);

        $this->assertSame(100.0, $this->invokePrivate('calculateVariation', [0.0, 25.0]));
        $this->assertSame(0.0, $this->invokePrivate('calculateVariation', [0.0, 0.0]));
    }

    public function testGenerateIncludesUncategorizedExpensesInCategoryComparison(): void
    {
        Lancamento::query()->delete();

        Lancamento::create([
            'user_id' => 77,
            'tipo' => 'despesa',
            'data' => '2026-03-05',
            'valor' => 200.00,
            'categoria_id' => null,
            'forma_pagamento' => 'Pix',
            'pago' => true,
            'afeta_caixa' => true,
            'eh_transferencia' => false,
        ]);

        Lancamento::create([
            'user_id' => 77,
            'tipo' => 'despesa',
            'data' => '2026-02-05',
            'valor' => 120.00,
            'categoria_id' => null,
            'forma_pagamento' => 'Pix',
            'pago' => true,
            'afeta_caixa' => true,
            'eh_transferencia' => false,
        ]);

        $comparatives = $this->service->generate(77, 2026, 3);

        $this->assertSame('Sem categoria', $comparatives['categories'][0]['nome']);
        $this->assertSame(200.0, $comparatives['categories'][0]['atual']);
        $this->assertSame(120.0, $comparatives['categories'][0]['anterior']);
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
            fn(...$args) => $this->{$method}(...$args),
            $this->service,
            $this->service
        );

        return $caller(...$arguments);
    }
}
