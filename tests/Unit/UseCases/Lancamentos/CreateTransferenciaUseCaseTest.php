<?php

declare(strict_types=1);

namespace Tests\Unit\UseCases\Lancamentos;

use Application\Models\Lancamento;
use Application\Services\Conta\TransferenciaService;
use Application\UseCases\Lancamentos\CreateTransferenciaUseCase;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use ValueError;

class CreateTransferenciaUseCaseTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testExecuteReturns422WhenDateIsInvalid(): void
    {
        $service = Mockery::mock(TransferenciaService::class);
        $service->shouldNotReceive('executarTransferencia');

        $useCase = new CreateTransferenciaUseCase($service);
        $result = $useCase->execute(10, [
            'data' => '31/03/2026',
            'valor' => 100,
            'conta_id' => 1,
            'conta_id_destino' => 2,
        ]);

        $this->assertTrue($result->isError());
        $this->assertSame(422, $result->httpCode);
        $this->assertSame('Data invalida (YYYY-MM-DD).', $result->message);
    }

    public function testExecuteReturns422WhenServiceThrowsValueError(): void
    {
        $service = Mockery::mock(TransferenciaService::class);
        $service->shouldReceive('executarTransferencia')
            ->once()
            ->andThrow(new ValueError('Selecione contas de origem e destino diferentes.'));

        $useCase = new CreateTransferenciaUseCase($service);
        $result = $useCase->execute(10, [
            'data' => '2026-03-31',
            'valor' => 120,
            'conta_id' => 1,
            'conta_id_destino' => 1,
        ]);

        $this->assertTrue($result->isError());
        $this->assertSame(422, $result->httpCode);
        $this->assertSame('Selecione contas de origem e destino diferentes.', $result->message);
    }

    public function testExecuteReturnsSuccessPayloadWithCreatedId(): void
    {
        $transferencia = new Lancamento();
        $transferencia->id = 321;

        $service = Mockery::mock(TransferenciaService::class);
        $service->shouldReceive('executarTransferencia')
            ->once()
            ->with(
                10,
                12,
                14,
                1234.56,
                '2026-03-31',
                'Transferencia mensal',
                'Reserva',
                null,
                null
            )
            ->andReturn($transferencia);

        $useCase = new CreateTransferenciaUseCase($service);
        $result = $useCase->execute(10, [
            'data' => '2026-03-31',
            'valor' => 'R$ 1.234,56',
            'conta_id' => 12,
            'conta_id_destino' => 14,
            'descricao' => 'Transferencia mensal',
            'observacao' => 'Reserva',
        ]);

        $this->assertFalse($result->isError());
        $this->assertSame(201, $result->httpCode);
        $this->assertSame('Success', $result->message);
        $this->assertSame(321, $result->data['id'] ?? null);
    }
}
