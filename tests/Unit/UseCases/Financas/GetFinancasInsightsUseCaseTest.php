<?php

declare(strict_types=1);

namespace Tests\Unit\UseCases\Financas;

use Application\Services\Demo\DemoPreviewService;
use Application\Services\Orcamentos\OrcamentoService;
use Application\UseCases\Financas\GetFinancasInsightsUseCase;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class GetFinancasInsightsUseCaseTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testExecuteReturnsPreviewInsightsWhenPreviewModeIsEnabled(): void
    {
        $previewPayload = ['insights' => [['tipo' => 'alerta']], 'meta' => ['is_demo' => true]];

        $orcamentoService = Mockery::mock(OrcamentoService::class);
        $orcamentoService->shouldNotReceive('getInsights');

        $previewService = Mockery::mock(DemoPreviewService::class);
        $previewService->shouldReceive('shouldUsePreview')->once()->with(10)->andReturnTrue();
        $previewService->shouldReceive('financeInsights')->once()->with(4, 2026)->andReturn($previewPayload);

        $useCase = new GetFinancasInsightsUseCase($orcamentoService, $previewService);
        $result = $useCase->execute(10, 4, 2026);

        $this->assertFalse($result->isError());
        $this->assertSame(200, $result->httpCode);
        $this->assertSame('Insights carregados', $result->message);
        $this->assertSame($previewPayload, $result->data);
    }

    public function testExecuteReturnsRealInsightsWhenPreviewModeIsDisabled(): void
    {
        $insights = [['tipo' => 'perigo']];

        $orcamentoService = Mockery::mock(OrcamentoService::class);
        $orcamentoService->shouldReceive('getInsights')->once()->with(10, 4, 2026)->andReturn($insights);

        $previewService = Mockery::mock(DemoPreviewService::class);
        $previewService->shouldReceive('shouldUsePreview')->once()->with(10)->andReturnFalse();
        $previewService->shouldNotReceive('financeInsights');

        $useCase = new GetFinancasInsightsUseCase($orcamentoService, $previewService);
        $result = $useCase->execute(10, 4, 2026);

        $this->assertFalse($result->isError());
        $this->assertSame('Insights carregados', $result->message);
        $this->assertSame($insights, $result->data);
    }
}
