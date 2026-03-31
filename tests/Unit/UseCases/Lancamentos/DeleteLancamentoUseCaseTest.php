<?php

declare(strict_types=1);

namespace Tests\Unit\UseCases\Lancamentos;

use Application\Models\Lancamento;
use Application\Repositories\LancamentoRepository;
use Application\Services\Lancamento\LancamentoDeletionService;
use Application\UseCases\Lancamentos\DeleteLancamentoUseCase;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class DeleteLancamentoUseCaseTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testExecuteReturnsErrorWhenLancamentoIsMissing(): void
    {
        $repo = Mockery::mock(LancamentoRepository::class);
        $repo->shouldReceive('findByIdAndUser')->once()->with(5, 10)->andReturnNull();

        $deletionService = Mockery::mock(LancamentoDeletionService::class);
        $deletionService->shouldNotReceive('delete');

        $useCase = new DeleteLancamentoUseCase($repo, $deletionService);
        $result = $useCase->execute(10, 5, 'single');

        $this->assertTrue($result->isError());
        $this->assertSame(404, $result->httpCode);
        $this->assertSame('Lancamento nao encontrado', $result->message);
    }

    public function testExecuteReturnsSuccessWithDeletionPayload(): void
    {
        $repo = Mockery::mock(LancamentoRepository::class);
        $lancamento = new Lancamento();
        $lancamento->id = 7;

        $repo->shouldReceive('findByIdAndUser')->once()->with(7, 10)->andReturn($lancamento);

        $deletionService = Mockery::mock(LancamentoDeletionService::class);
        $deletionService
            ->shouldReceive('delete')
            ->once()
            ->with($lancamento, 10, 'future')
            ->andReturn([
                'ok' => true,
                'message' => '2 lancamentos futuros da recorrencia excluidos',
                'excluidos' => 2,
            ]);

        $useCase = new DeleteLancamentoUseCase($repo, $deletionService);
        $result = $useCase->execute(10, 7, 'future');

        $this->assertFalse($result->isError());
        $this->assertSame(true, $result->data['ok'] ?? null);
        $this->assertSame(2, $result->data['excluidos'] ?? null);
    }
}
