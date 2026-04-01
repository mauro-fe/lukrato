<?php

declare(strict_types=1);

namespace Tests\Unit\UseCases\Orcamentos;

use Application\Services\Orcamentos\OrcamentoService;
use Application\UseCases\Orcamentos\BulkSaveOrcamentosUseCase;
use DomainException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class BulkSaveOrcamentosUseCaseTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testExecuteReturnsValidationFailWhenPayloadIsInvalid(): void
    {
        $service = Mockery::mock(OrcamentoService::class);
        $service->shouldNotReceive('salvarMultiplos');

        $useCase = new BulkSaveOrcamentosUseCase($service);
        $result = $useCase->execute(10, []);

        $this->assertTrue($result->isValidationError());
        $this->assertSame(422, $result->httpCode);
        $this->assertArrayHasKey('orcamentos', $result->data['errors'] ?? []);
    }

    public function testExecuteReturns403WhenServiceThrowsDomainException(): void
    {
        $service = Mockery::mock(OrcamentoService::class);
        $service->shouldReceive('salvarMultiplos')
            ->once()
            ->with(10, 4, 2026, Mockery::type('array'))
            ->andThrow(new DomainException('Você só pode criar mais 1 orçamento.'));

        $useCase = new BulkSaveOrcamentosUseCase($service);
        $result = $useCase->execute(10, [
            'mes' => 4,
            'ano' => 2026,
            'orcamentos' => [
                ['categoria_id' => 1, 'valor_limite' => 100],
            ],
        ]);

        $this->assertTrue($result->isError());
        $this->assertSame(403, $result->httpCode);
        $this->assertSame('Você só pode criar mais 1 orçamento.', $result->message);
    }

    public function testExecuteReturnsSuccessPayloadWhenBulkSaveSucceeds(): void
    {
        $payload = [['id' => 1], ['id' => 2]];

        $service = Mockery::mock(OrcamentoService::class);
        $service->shouldReceive('salvarMultiplos')
            ->once()
            ->with(10, 4, 2026, Mockery::type('array'))
            ->andReturn($payload);

        $useCase = new BulkSaveOrcamentosUseCase($service);
        $result = $useCase->execute(10, [
            'mes' => 4,
            'ano' => 2026,
            'orcamentos' => [
                ['categoria_id' => 1, 'valor_limite' => 100],
            ],
        ]);

        $this->assertFalse($result->isError());
        $this->assertSame(200, $result->httpCode);
        $this->assertSame('Orçamentos salvos com sucesso!', $result->message);
        $this->assertSame($payload, $result->data);
    }
}
