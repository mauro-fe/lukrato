<?php

declare(strict_types=1);

namespace Tests\Unit\UseCases\Orcamentos;

use Application\Services\Orcamentos\OrcamentoService;
use Application\UseCases\Orcamentos\ApplyOrcamentoSugestoesUseCase;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class ApplyOrcamentoSugestoesUseCaseTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testExecuteAppliesProvidedSuggestions(): void
    {
        $expected = [['id' => 10]];

        $service = Mockery::mock(OrcamentoService::class);
        $service->shouldReceive('aplicarSugestoes')
            ->once()
            ->with(10, 4, 2026, Mockery::type('array'))
            ->andReturn($expected);

        $useCase = new ApplyOrcamentoSugestoesUseCase($service);
        $result = $useCase->execute(10, [
            'mes' => 4,
            'ano' => 2026,
            'sugestoes' => [
                ['categoria_id' => 1, 'valor_sugerido' => 300],
            ],
        ]);

        $this->assertFalse($result->isError());
        $this->assertSame(200, $result->httpCode);
        $this->assertSame('Sugestões aplicadas com sucesso!', $result->message);
        $this->assertSame($expected, $result->data);
    }

    public function testExecuteUsesDefaultsWhenMonthYearAndSuggestionsAreMissing(): void
    {
        $expected = [];

        $service = Mockery::mock(OrcamentoService::class);
        $service->shouldReceive('aplicarSugestoes')
            ->once()
            ->with(10, Mockery::type('int'), Mockery::type('int'), [])
            ->andReturn($expected);

        $useCase = new ApplyOrcamentoSugestoesUseCase($service);
        $result = $useCase->execute(10, []);

        $this->assertFalse($result->isError());
        $this->assertSame($expected, $result->data);
    }
}
