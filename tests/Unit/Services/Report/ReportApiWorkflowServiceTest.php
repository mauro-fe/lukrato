<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Report;

use Application\Builders\ReportExportBuilder;
use Application\DTO\ReportData;
use Application\DTO\ReportParameters;
use Application\Enums\GamificationAction;
use Application\Enums\ReportType;
use Application\Models\Usuario;
use Application\Services\Gamification\GamificationService;
use Application\Services\Report\ComparativesService;
use Application\Services\Report\ExcelExportService;
use Application\Services\Report\InsightsService;
use Application\Services\Report\PdfExportService;
use Application\Services\Report\ReportApiWorkflowService;
use Application\Services\Report\ReportService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class ReportApiWorkflowServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testGenerateReportBuildsParametersAndEnablesDetailsForProCategoryReport(): void
    {
        $reportService = Mockery::mock(ReportService::class);
        $reportService
            ->shouldReceive('generateReport')
            ->once()
            ->with(
                ReportType::DESPESAS_POR_CATEGORIA,
                Mockery::on(static function (ReportParameters $params): bool {
                    return $params->userId === 77
                        && $params->accountId === null
                        && $params->includeTransfers === false
                        && $params->start->toDateString() === '2026-03-01'
                        && $params->end->toDateString() === '2026-03-31';
                }),
                true
            )
            ->andReturn(['labels' => ['Moradia'], 'values' => [500.0]]);

        $service = new ReportApiWorkflowService(
            $reportService,
            Mockery::mock(ReportExportBuilder::class),
            Mockery::mock(PdfExportService::class),
            Mockery::mock(ExcelExportService::class),
            Mockery::mock(InsightsService::class),
            Mockery::mock(ComparativesService::class),
        );

        $user = new class extends Usuario {
            public function isPro(): bool
            {
                return true;
            }
        };

        $result = $service->generateReport(77, $user, [
            'month' => '2026-03',
            'type' => 'despesas_por_categoria',
        ]);

        $this->assertSame(['Moradia'], $result['result']['labels']);
        $this->assertSame(ReportType::DESPESAS_POR_CATEGORIA, $result['type']);
        $this->assertSame('2026-03-01', $result['params']->start->toDateString());
        $this->assertSame('2026-03-31', $result['params']->end->toDateString());
    }

    public function testGenerateReportTracksReportViewInGamification(): void
    {
        $reportService = Mockery::mock(ReportService::class);
        $reportService
            ->shouldReceive('generateReport')
            ->once()
            ->andReturn(['labels' => ['Moradia'], 'values' => [500.0]]);

        $gamificationService = Mockery::mock(GamificationService::class);
        $gamificationService
            ->shouldReceive('addPoints')
            ->once()
            ->with(
                77,
                GamificationAction::VIEW_REPORT,
                null,
                null,
                Mockery::on(static function (array $metadata): bool {
                    return $metadata['report_type'] === 'despesas_por_categoria'
                        && $metadata['period'] === '2026-03'
                        && $metadata['account_id'] === null;
                })
            )
            ->andReturn(['success' => true]);

        $service = new ReportApiWorkflowService(
            $reportService,
            Mockery::mock(ReportExportBuilder::class),
            Mockery::mock(PdfExportService::class),
            Mockery::mock(ExcelExportService::class),
            Mockery::mock(InsightsService::class),
            Mockery::mock(ComparativesService::class),
            $gamificationService,
        );

        $user = new class extends Usuario {
            public function isPro(): bool
            {
                return true;
            }
        };

        $service->generateReport(77, $user, [
            'month' => '2026-03',
            'type' => 'despesas_por_categoria',
        ]);
    }

    public function testGenerateAnnualCategoryReportUsesOnlySelectedCalendarYear(): void
    {
        $reportService = Mockery::mock(ReportService::class);
        $reportService
            ->shouldReceive('generateReport')
            ->once()
            ->with(
                ReportType::DESPESAS_ANUAIS_POR_CATEGORIA,
                Mockery::on(static function (ReportParameters $params): bool {
                    return $params->userId === 77
                        && $params->accountId === 12
                        && $params->includeTransfers === false
                        && $params->start->toDateString() === '2026-01-01'
                        && $params->end->toDateString() === '2026-12-31';
                }),
                true
            )
            ->andReturn(['labels' => ['Moradia'], 'values' => [500.0]]);

        $service = new ReportApiWorkflowService(
            $reportService,
            Mockery::mock(ReportExportBuilder::class),
            Mockery::mock(PdfExportService::class),
            Mockery::mock(ExcelExportService::class),
            Mockery::mock(InsightsService::class),
            Mockery::mock(ComparativesService::class),
        );

        $user = new class extends Usuario {
            public function isPro(): bool
            {
                return true;
            }
        };

        $result = $service->generateReport(77, $user, [
            'year' => '2026',
            'month' => '04',
            'account_id' => '12',
            'type' => 'despesas_anuais_por_categoria',
        ]);

        $this->assertSame(ReportType::DESPESAS_ANUAIS_POR_CATEGORIA, $result['type']);
        $this->assertSame('2026-01-01', $result['params']->start->toDateString());
        $this->assertSame('2026-12-31', $result['params']->end->toDateString());
    }

    public function testExportReportBuildsExcelFileMetadata(): void
    {
        $reportService = Mockery::mock(ReportService::class);
        $reportService
            ->shouldReceive('generateReport')
            ->once()
            ->andReturn(['labels' => ['Salario'], 'values' => [1000.0]]);

        $builder = Mockery::mock(ReportExportBuilder::class);
        $builder
            ->shouldReceive('build')
            ->once()
            ->with(
                ReportType::RECEITAS_POR_CATEGORIA,
                Mockery::type(ReportParameters::class),
                ['labels' => ['Salario'], 'values' => [1000.0]]
            )
            ->andReturn(new ReportData(
                title: 'Relatório',
                headers: ['Categoria', 'Valor'],
                rows: [['Salario', '1000,00']]
            ));

        $excelExport = Mockery::mock(ExcelExportService::class);
        $excelExport
            ->shouldReceive('export')
            ->once()
            ->andReturn('XLSXDATA');

        $gamificationService = Mockery::mock(GamificationService::class);
        $gamificationService
            ->shouldReceive('addPoints')
            ->never();

        $service = new ReportApiWorkflowService(
            $reportService,
            $builder,
            Mockery::mock(PdfExportService::class),
            $excelExport,
            Mockery::mock(InsightsService::class),
            Mockery::mock(ComparativesService::class),
            $gamificationService,
        );

        $user = new class extends Usuario {
            public function isPro(): bool
            {
                return true;
            }
        };

        $result = $service->exportReport(88, $user, [
            'month' => '2026-03',
            'type' => 'receitas_por_categoria',
            'format' => 'excel',
        ]);

        $this->assertSame('XLSXDATA', $result['content']);
        $this->assertSame('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $result['mime']);
        $this->assertSame('receitas_por_categoria_2026_03.xlsx', $result['filename']);
    }

    public function testBuildSummaryAggregatesCurrentAndPreviousPeriod(): void
    {
        $currentLancamentoQuery = Mockery::mock();
        $currentLancamentoQuery->shouldReceive('whereBetween')->once()->with('data', ['2026-03-01', '2026-03-31'])->andReturnSelf();
        $currentLancamentoQuery->shouldReceive('where')->once()->with('eh_transferencia', 0)->andReturnSelf();
        $currentLancamentoQuery->shouldReceive('where')->once()->with('pago', 1)->andReturnSelf();
        $currentLancamentoQuery->shouldReceive('where')->once()->with('afeta_caixa', 1)->andReturnSelf();
        $currentLancamentoQuery->shouldReceive('selectRaw')->once()->andReturnSelf();
        $currentLancamentoQuery->shouldReceive('first')->once()->andReturn((object) [
            'total_receitas' => 1000.0,
            'total_despesas' => 400.0,
        ]);

        $previousLancamentoQuery = Mockery::mock();
        $previousLancamentoQuery->shouldReceive('whereBetween')->once()->with('data', ['2026-02-01', '2026-02-28'])->andReturnSelf();
        $previousLancamentoQuery->shouldReceive('where')->once()->with('eh_transferencia', 0)->andReturnSelf();
        $previousLancamentoQuery->shouldReceive('where')->once()->with('pago', 1)->andReturnSelf();
        $previousLancamentoQuery->shouldReceive('where')->once()->with('afeta_caixa', 1)->andReturnSelf();
        $previousLancamentoQuery->shouldReceive('selectRaw')->once()->andReturnSelf();
        $previousLancamentoQuery->shouldReceive('first')->once()->andReturn((object) [
            'total_receitas' => 800.0,
            'total_despesas' => 500.0,
        ]);

        $lancamentoModel = Mockery::mock('alias:Application\Models\Lancamento');
        $lancamentoModel->shouldReceive('where')->twice()->with('user_id', 99)->andReturn(
            $currentLancamentoQuery,
            $previousLancamentoQuery
        );

        $currentCardsQuery = Mockery::mock();
        $currentCardsQuery->shouldReceive('where')->once()->with('mes_referencia', 3)->andReturnSelf();
        $currentCardsQuery->shouldReceive('where')->once()->with('ano_referencia', 2026)->andReturnSelf();
        $currentCardsQuery->shouldReceive('whereHas')->once()->with('cartaoCredito', Mockery::type(\Closure::class))->andReturnSelf();
        $currentCardsQuery->shouldReceive('sum')->once()->with('valor')->andReturn(250.0);

        $previousCardsQuery = Mockery::mock();
        $previousCardsQuery->shouldReceive('where')->once()->with('mes_referencia', 2)->andReturnSelf();
        $previousCardsQuery->shouldReceive('where')->once()->with('ano_referencia', 2026)->andReturnSelf();
        $previousCardsQuery->shouldReceive('whereHas')->once()->with('cartaoCredito', Mockery::type(\Closure::class))->andReturnSelf();
        $previousCardsQuery->shouldReceive('sum')->once()->with('valor')->andReturn(150.0);

        $cardsModel = Mockery::mock('alias:Application\Models\FaturaCartaoItem');
        $cardsModel->shouldReceive('where')->twice()->with('user_id', 99)->andReturn(
            $currentCardsQuery,
            $previousCardsQuery
        );

        $service = new ReportApiWorkflowService(
            Mockery::mock(ReportService::class),
            Mockery::mock(ReportExportBuilder::class),
            Mockery::mock(PdfExportService::class),
            Mockery::mock(ExcelExportService::class),
            Mockery::mock(InsightsService::class),
            Mockery::mock(ComparativesService::class),
        );

        $result = $service->buildSummary(99, [
            'year' => '2026',
            'month' => '3',
        ]);

        $this->assertSame(1000.0, $result['totalReceitas']);
        $this->assertSame(400.0, $result['totalDespesas']);
        $this->assertSame(600.0, $result['saldo']);
        $this->assertSame(250.0, $result['totalCartoes']);
        $this->assertSame(800.0, $result['prevReceitas']);
        $this->assertSame(500.0, $result['prevDespesas']);
        $this->assertSame(300.0, $result['prevSaldo']);
        $this->assertSame(150.0, $result['prevCartoes']);
    }
}
