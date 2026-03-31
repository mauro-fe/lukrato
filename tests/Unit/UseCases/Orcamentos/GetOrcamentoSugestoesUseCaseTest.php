<?php

declare(strict_types=1);

namespace Tests\Unit\UseCases\Orcamentos;

use Application\Services\Financeiro\OrcamentoService;
use Application\UseCases\Orcamentos\GetOrcamentoSugestoesUseCase;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class GetOrcamentoSugestoesUseCaseTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testExecuteReturnsSuggestionsFromService(): void
    {
        $expected = [
            ['categoria_id' => 1, 'valor_sugerido' => 300.0],
        ];

        $service = Mockery::mock(OrcamentoService::class);
        $service->shouldReceive('autoSugerir')
            ->once()
            ->with(10)
            ->andReturn($expected);

        $useCase = new GetOrcamentoSugestoesUseCase($service);
        $result = $useCase->execute(10);

        $this->assertFalse($result->isError());
        $this->assertSame(200, $result->httpCode);
        $this->assertSame('Sugestões calculadas', $result->message);
        $this->assertSame($expected, $result->data);
    }
}
