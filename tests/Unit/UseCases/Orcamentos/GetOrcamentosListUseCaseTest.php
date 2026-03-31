<?php

declare(strict_types=1);

namespace Tests\Unit\UseCases\Orcamentos;

use Application\Services\Demo\DemoPreviewService;
use Application\Services\Financeiro\OrcamentoService;
use Application\UseCases\Orcamentos\GetOrcamentosListUseCase;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class GetOrcamentosListUseCaseTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testExecuteReturnsPreviewBudgetsWhenPreviewModeIsEnabled(): void
    {
        $previewPayload = ['orcamentos' => [['id' => -1]], 'meta' => ['is_demo' => true]];

        $orcamentoService = Mockery::mock(OrcamentoService::class);
        $orcamentoService->shouldNotReceive('listarComProgresso');

        $previewService = Mockery::mock(DemoPreviewService::class);
        $previewService->shouldReceive('shouldUsePreview')->once()->with(10)->andReturnTrue();
        $previewService->shouldReceive('orcamentos')->once()->with(4, 2026)->andReturn($previewPayload);

        $useCase = new GetOrcamentosListUseCase($orcamentoService, $previewService);
        $result = $useCase->execute(10, 4, 2026);

        $this->assertFalse($result->isError());
        $this->assertSame(200, $result->httpCode);
        $this->assertSame('Orçamentos carregados', $result->message);
        $this->assertSame($previewPayload, $result->data);
    }

    public function testExecuteReturnsRealBudgetsWhenPreviewModeIsDisabled(): void
    {
        $orcamentos = [['id' => 1, 'valor_limite' => 500.0]];

        $orcamentoService = Mockery::mock(OrcamentoService::class);
        $orcamentoService->shouldReceive('listarComProgresso')->once()->with(10, 4, 2026)->andReturn($orcamentos);

        $previewService = Mockery::mock(DemoPreviewService::class);
        $previewService->shouldReceive('shouldUsePreview')->once()->with(10)->andReturnFalse();
        $previewService->shouldNotReceive('orcamentos');

        $useCase = new GetOrcamentosListUseCase($orcamentoService, $previewService);
        $result = $useCase->execute(10, 4, 2026);

        $this->assertFalse($result->isError());
        $this->assertSame('Orçamentos carregados', $result->message);
        $this->assertSame($orcamentos, $result->data);
    }
}
