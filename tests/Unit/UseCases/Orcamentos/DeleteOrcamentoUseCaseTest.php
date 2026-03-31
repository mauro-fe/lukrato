<?php

declare(strict_types=1);

namespace Tests\Unit\UseCases\Orcamentos;

use Application\Services\Financeiro\OrcamentoService;
use Application\UseCases\Orcamentos\DeleteOrcamentoUseCase;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class DeleteOrcamentoUseCaseTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testExecuteReturnsNotFoundWhenBudgetDoesNotExist(): void
    {
        $service = Mockery::mock(OrcamentoService::class);
        $service->shouldReceive('remover')
            ->once()
            ->with(10, 22)
            ->andReturnFalse();

        $useCase = new DeleteOrcamentoUseCase($service);
        $result = $useCase->execute(10, 22);

        $this->assertTrue($result->isError());
        $this->assertSame(404, $result->httpCode);
        $this->assertSame('Orçamento não encontrado.', $result->message);
    }

    public function testExecuteReturnsSuccessWhenBudgetIsRemoved(): void
    {
        $service = Mockery::mock(OrcamentoService::class);
        $service->shouldReceive('remover')
            ->once()
            ->with(10, 22)
            ->andReturnTrue();

        $useCase = new DeleteOrcamentoUseCase($service);
        $result = $useCase->execute(10, 22);

        $this->assertFalse($result->isError());
        $this->assertSame(200, $result->httpCode);
        $this->assertSame('Orçamento removido com sucesso!', $result->message);
        $this->assertSame([], $result->data);
    }
}
