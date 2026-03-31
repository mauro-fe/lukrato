<?php

declare(strict_types=1);

namespace Tests\Unit\UseCases\Lancamentos;

use Application\Models\Lancamento;
use Application\Repositories\LancamentoRepository;
use Application\Services\Lancamento\LancamentoDeletionService;
use Application\UseCases\Lancamentos\BulkDeleteLancamentosUseCase;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class BulkDeleteLancamentosUseCaseTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testExecuteReturnsValidationErrorWhenIdsAreMissing(): void
    {
        $useCase = new BulkDeleteLancamentosUseCase(
            Mockery::mock(LancamentoRepository::class),
            Mockery::mock(LancamentoDeletionService::class)
        );

        $result = $useCase->execute(10, []);

        $this->assertTrue($result->isError());
        $this->assertSame(422, $result->httpCode);
    }

    public function testExecuteReturnsValidationErrorWhenIdsExceedLimit(): void
    {
        $useCase = new BulkDeleteLancamentosUseCase(
            Mockery::mock(LancamentoRepository::class),
            Mockery::mock(LancamentoDeletionService::class)
        );

        $result = $useCase->execute(10, range(1, 101));

        $this->assertTrue($result->isError());
        $this->assertSame(422, $result->httpCode);
    }

    public function testExecuteAggregatesDeletedCountAndErrors(): void
    {
        $repo = Mockery::mock(LancamentoRepository::class);
        $deletionService = Mockery::mock(LancamentoDeletionService::class);

        $lancamento = new Lancamento();
        $lancamento->id = 1;

        $repo->shouldReceive('findByIdAndUser')->once()->with(1, 10)->andReturn($lancamento);
        $repo->shouldReceive('findByIdAndUser')->once()->with(2, 10)->andReturnNull();

        $deletionService->shouldReceive('delete')
            ->once()
            ->with($lancamento, 10, 'single')
            ->andReturn(['ok' => true, 'message' => 'Lancamento excluido', 'excluidos' => 1]);

        $useCase = new BulkDeleteLancamentosUseCase($repo, $deletionService);
        $result = $useCase->execute(10, [1, 2]);

        $this->assertFalse($result->isError());
        $this->assertSame(1, $result->data['deleted'] ?? null);
        $this->assertCount(1, $result->data['errors'] ?? []);
    }
}
