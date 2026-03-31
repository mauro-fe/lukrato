<?php

declare(strict_types=1);

namespace Tests\Unit\UseCases\Lancamentos;

use Application\Models\Lancamento;
use Application\Repositories\LancamentoRepository;
use Application\Repositories\ParcelamentoRepository;
use Application\Services\Lancamento\LancamentoStatusService;
use Application\UseCases\Lancamentos\ToggleLancamentoPagoUseCase;
use DomainException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class ToggleLancamentoPagoUseCaseTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testExecuteReturnsNotFoundWhenLancamentoIsMissing(): void
    {
        $repo = Mockery::mock(LancamentoRepository::class);
        $repo->shouldReceive('findByIdAndUser')->once()->with(8, 10)->andReturnNull();

        $statusService = Mockery::mock(LancamentoStatusService::class);
        $statusService->shouldNotReceive('marcarPago');
        $statusService->shouldNotReceive('desmarcarPago');

        $parcelamentoRepo = Mockery::mock(ParcelamentoRepository::class);
        $parcelamentoRepo->shouldNotReceive('atualizarParcelasPagas');

        $useCase = new ToggleLancamentoPagoUseCase($repo, $statusService, $parcelamentoRepo);
        $result = $useCase->execute(10, 8, true);

        $this->assertTrue($result->isError());
        $this->assertSame(404, $result->httpCode);
        $this->assertSame('Lancamento nao encontrado', $result->message);
    }

    public function testExecuteMarksAsPaidAndSyncsInstallmentsWhenNeeded(): void
    {
        $repo = Mockery::mock(LancamentoRepository::class);
        $statusService = Mockery::mock(LancamentoStatusService::class);
        $parcelamentoRepo = Mockery::mock(ParcelamentoRepository::class);

        $lancamento = new Lancamento();
        $lancamento->id = 8;
        $lancamento->parcelamento_id = 44;

        $repo->shouldReceive('findByIdAndUser')->once()->with(8, 10)->andReturn($lancamento);
        $statusService->shouldReceive('marcarPago')->once()->with($lancamento)->andReturn($lancamento);
        $parcelamentoRepo->shouldReceive('atualizarParcelasPagas')->once()->with(44);

        $useCase = new ToggleLancamentoPagoUseCase($repo, $statusService, $parcelamentoRepo);
        $result = $useCase->execute(10, 8, true);

        $this->assertFalse($result->isError());
        $this->assertSame('Lancamento marcado como pago.', $result->message);
        $this->assertSame($lancamento, $result->data['lancamento'] ?? null);
    }

    public function testExecuteReturnsDomainErrorWithStatus422(): void
    {
        $repo = Mockery::mock(LancamentoRepository::class);
        $statusService = Mockery::mock(LancamentoStatusService::class);
        $parcelamentoRepo = Mockery::mock(ParcelamentoRepository::class);

        $lancamento = new Lancamento();
        $lancamento->id = 9;
        $lancamento->parcelamento_id = 0;

        $repo->shouldReceive('findByIdAndUser')->once()->with(9, 10)->andReturn($lancamento);
        $statusService->shouldReceive('desmarcarPago')
            ->once()
            ->with($lancamento)
            ->andThrow(new DomainException('Nao foi possivel desmarcar.'));
        $parcelamentoRepo->shouldNotReceive('atualizarParcelasPagas');

        $useCase = new ToggleLancamentoPagoUseCase($repo, $statusService, $parcelamentoRepo);
        $result = $useCase->execute(10, 9, false);

        $this->assertTrue($result->isError());
        $this->assertSame(422, $result->httpCode);
        $this->assertSame('Nao foi possivel desmarcar.', $result->message);
    }
}
