<?php

declare(strict_types=1);

namespace Tests\Unit\UseCases\Metas;

use Application\Services\Financeiro\MetaService;
use Application\UseCases\Metas\DeleteMetaUseCase;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class DeleteMetaUseCaseTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testExecuteReturnsNotFoundWhenMetaDoesNotExist(): void
    {
        $service = Mockery::mock(MetaService::class);
        $service->shouldReceive('remover')
            ->once()
            ->with(10, 7)
            ->andReturnFalse();

        $useCase = new DeleteMetaUseCase($service);
        $result = $useCase->execute(10, 7);

        $this->assertTrue($result->isError());
        $this->assertSame(404, $result->httpCode);
        $this->assertSame('Meta não encontrada.', $result->message);
    }

    public function testExecuteReturnsSuccessWhenMetaIsRemoved(): void
    {
        $service = Mockery::mock(MetaService::class);
        $service->shouldReceive('remover')
            ->once()
            ->with(10, 7)
            ->andReturnTrue();

        $useCase = new DeleteMetaUseCase($service);
        $result = $useCase->execute(10, 7);

        $this->assertFalse($result->isError());
        $this->assertSame(200, $result->httpCode);
        $this->assertSame('Meta removida com sucesso!', $result->message);
        $this->assertSame([], $result->data);
    }
}
