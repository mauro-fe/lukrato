<?php

declare(strict_types=1);

namespace Tests\Unit\UseCases\Metas;

use Application\Services\Demo\DemoPreviewService;
use Application\Services\Financeiro\MetaService;
use Application\UseCases\Metas\GetMetasListUseCase;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class GetMetasListUseCaseTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testExecuteReturnsPreviewGoalsWhenPreviewModeIsEnabled(): void
    {
        $previewPayload = ['metas' => [['id' => -1]], 'meta' => ['is_demo' => true]];

        $metaService = Mockery::mock(MetaService::class);
        $metaService->shouldNotReceive('listar');

        $previewService = Mockery::mock(DemoPreviewService::class);
        $previewService->shouldReceive('shouldUsePreview')->once()->with(10)->andReturnTrue();
        $previewService->shouldReceive('metas')->once()->with('ativa')->andReturn($previewPayload);

        $useCase = new GetMetasListUseCase($metaService, $previewService);
        $result = $useCase->execute(10, 'ativa');

        $this->assertFalse($result->isError());
        $this->assertSame(200, $result->httpCode);
        $this->assertSame('Metas carregadas', $result->message);
        $this->assertSame($previewPayload, $result->data);
    }

    public function testExecuteReturnsRealGoalsWhenPreviewModeIsDisabled(): void
    {
        $metas = [['id' => 1, 'titulo' => 'Meta real']];

        $metaService = Mockery::mock(MetaService::class);
        $metaService->shouldReceive('listar')->once()->with(10, null)->andReturn($metas);

        $previewService = Mockery::mock(DemoPreviewService::class);
        $previewService->shouldReceive('shouldUsePreview')->once()->with(10)->andReturnFalse();
        $previewService->shouldNotReceive('metas');

        $useCase = new GetMetasListUseCase($metaService, $previewService);
        $result = $useCase->execute(10, null);

        $this->assertFalse($result->isError());
        $this->assertSame('Metas carregadas', $result->message);
        $this->assertSame($metas, $result->data);
    }
}
