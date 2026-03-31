<?php

declare(strict_types=1);

namespace Tests\Unit\UseCases\Orcamentos;

use Application\Services\Financeiro\OrcamentoService;
use Application\UseCases\Orcamentos\CopyOrcamentosMesUseCase;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class CopyOrcamentosMesUseCaseTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testExecuteCopiesWithProvidedMonthAndYear(): void
    {
        $expected = [
            'copiados' => 3,
            'orcamentos' => [['id' => 1]],
        ];

        $service = Mockery::mock(OrcamentoService::class);
        $service->shouldReceive('copiarMesAnterior')
            ->once()
            ->with(10, 5, 2026)
            ->andReturn($expected);

        $useCase = new CopyOrcamentosMesUseCase($service);
        $result = $useCase->execute(10, [
            'mes' => 5,
            'ano' => 2026,
        ]);

        $this->assertFalse($result->isError());
        $this->assertSame(200, $result->httpCode);
        $this->assertSame('3 orçamentos copiados!', $result->message);
        $this->assertSame($expected, $result->data);
    }

    public function testExecuteUsesCurrentMonthAndYearWhenMissingInPayload(): void
    {
        $expected = [
            'copiados' => 0,
            'orcamentos' => [],
        ];

        $service = Mockery::mock(OrcamentoService::class);
        $service->shouldReceive('copiarMesAnterior')
            ->once()
            ->with(10, Mockery::type('int'), Mockery::type('int'))
            ->andReturn($expected);

        $useCase = new CopyOrcamentosMesUseCase($service);
        $result = $useCase->execute(10, []);

        $this->assertFalse($result->isError());
        $this->assertSame(200, $result->httpCode);
        $this->assertSame('0 orçamentos copiados!', $result->message);
        $this->assertSame($expected, $result->data);
    }
}
