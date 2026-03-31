<?php

declare(strict_types=1);

namespace Tests\Unit\UseCases\Orcamentos;

use Application\Services\Financeiro\OrcamentoService;
use Application\UseCases\Orcamentos\SaveOrcamentoUseCase;
use DomainException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class SaveOrcamentoUseCaseTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testExecuteReturnsValidationFailWhenPayloadIsInvalid(): void
    {
        $service = Mockery::mock(OrcamentoService::class);
        $service->shouldNotReceive('salvar');

        $useCase = new SaveOrcamentoUseCase($service);
        $result = $useCase->execute(10, []);

        $this->assertTrue($result->isValidationError());
        $this->assertSame(422, $result->httpCode);
        $this->assertArrayHasKey('categoria_id', $result->data['errors'] ?? []);
        $this->assertArrayHasKey('valor_limite', $result->data['errors'] ?? []);
    }

    public function testExecuteReturns403WhenServiceThrowsDomainException(): void
    {
        $service = Mockery::mock(OrcamentoService::class);
        $service->shouldReceive('salvar')
            ->once()
            ->with(10, 3, 4, 2026, Mockery::type('array'))
            ->andThrow(new DomainException('Limite de orçamentos atingido.'));

        $useCase = new SaveOrcamentoUseCase($service);
        $result = $useCase->execute(10, [
            'categoria_id' => 3,
            'valor_limite' => 500,
            'mes' => 4,
            'ano' => 2026,
        ]);

        $this->assertTrue($result->isError());
        $this->assertSame(403, $result->httpCode);
        $this->assertSame('Limite de orçamentos atingido.', $result->message);
    }

    public function testExecuteReturnsSuccessPayloadWhenSaved(): void
    {
        $payload = [['id' => 1, 'valor_limite' => 500.0]];

        $service = Mockery::mock(OrcamentoService::class);
        $service->shouldReceive('salvar')
            ->once()
            ->with(10, 3, 4, 2026, Mockery::type('array'))
            ->andReturn($payload);

        $useCase = new SaveOrcamentoUseCase($service);
        $result = $useCase->execute(10, [
            'categoria_id' => 3,
            'valor_limite' => 500,
            'mes' => 4,
            'ano' => 2026,
        ]);

        $this->assertFalse($result->isError());
        $this->assertSame(200, $result->httpCode);
        $this->assertSame('Orçamento salvo com sucesso!', $result->message);
        $this->assertSame($payload, $result->data);
    }
}
