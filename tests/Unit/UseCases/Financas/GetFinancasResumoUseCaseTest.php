<?php

declare(strict_types=1);

namespace Tests\Unit\UseCases\Financas;

use Application\Services\Demo\DemoPreviewService;
use Application\Services\Metas\MetaService;
use Application\Services\Orcamentos\OrcamentoService;
use Application\UseCases\Financas\GetFinancasResumoUseCase;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class GetFinancasResumoUseCaseTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testExecuteReturnsPreviewSummaryWhenPreviewModeIsEnabled(): void
    {
        $previewPayload = ['orcamento' => ['total_categorias' => 5], 'mes' => 4, 'ano' => 2026];

        $metaService = Mockery::mock(MetaService::class);
        $metaService->shouldNotReceive('resumo');

        $orcamentoService = Mockery::mock(OrcamentoService::class);
        $orcamentoService->shouldNotReceive('resumo');
        $orcamentoService->shouldNotReceive('getInsights');

        $previewService = Mockery::mock(DemoPreviewService::class);
        $previewService->shouldReceive('shouldUsePreview')->once()->with(10)->andReturnTrue();
        $previewService->shouldReceive('financeSummary')->once()->with(4, 2026)->andReturn($previewPayload);

        $useCase = new GetFinancasResumoUseCase($metaService, $orcamentoService, $previewService);
        $result = $useCase->execute(10, 4, 2026);

        $this->assertFalse($result->isError());
        $this->assertSame(200, $result->httpCode);
        $this->assertSame('Resumo financeiro carregado', $result->message);
        $this->assertSame($previewPayload, $result->data);
    }

    public function testExecuteReturnsRealSummaryWhenPreviewModeIsDisabled(): void
    {
        $orcamentoResumo = ['total_categorias' => 4];
        $metasResumo = ['total_metas' => 2];
        $insights = [['tipo' => 'alerta']];

        $metaService = Mockery::mock(MetaService::class);
        $metaService->shouldReceive('resumo')->once()->with(10)->andReturn($metasResumo);

        $orcamentoService = Mockery::mock(OrcamentoService::class);
        $orcamentoService->shouldReceive('resumo')->once()->with(10, 4, 2026)->andReturn($orcamentoResumo);
        $orcamentoService->shouldReceive('getInsights')->once()->with(10, 4, 2026)->andReturn($insights);

        $previewService = Mockery::mock(DemoPreviewService::class);
        $previewService->shouldReceive('shouldUsePreview')->once()->with(10)->andReturnFalse();
        $previewService->shouldNotReceive('financeSummary');

        $useCase = new GetFinancasResumoUseCase($metaService, $orcamentoService, $previewService);
        $result = $useCase->execute(10, 4, 2026);

        $this->assertFalse($result->isError());
        $this->assertSame('Resumo financeiro carregado', $result->message);
        $this->assertSame([
            'orcamento' => $orcamentoResumo,
            'metas' => $metasResumo,
            'insights' => $insights,
            'mes' => 4,
            'ano' => 2026,
        ], $result->data);
    }
}
