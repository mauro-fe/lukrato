<?php

declare(strict_types=1);

namespace Tests\Unit\UseCases\Metas;

use Application\Models\Meta;
use Application\Services\Metas\MetaService;
use Application\UseCases\Metas\GetMetaTemplatesUseCase;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class GetMetaTemplatesUseCaseTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testExecuteAppliesEmergencySuggestionWhenPositive(): void
    {
        $templates = [
            ['tipo' => Meta::TIPO_EMERGENCIA, 'titulo' => 'Reserva', 'valor_sugerido' => null],
            ['tipo' => Meta::TIPO_COMPRA, 'titulo' => 'Celular', 'valor_sugerido' => 1200],
        ];

        $service = Mockery::mock(MetaService::class);
        $service->shouldReceive('getTemplates')->once()->andReturn($templates);
        $service->shouldReceive('sugerirReservaEmergencia')->once()->with(10)->andReturn(4500.0);

        $useCase = new GetMetaTemplatesUseCase($service);
        $result = $useCase->execute(10);

        $this->assertFalse($result->isError());
        $this->assertSame(200, $result->httpCode);
        $this->assertSame('Templates carregados', $result->message);
        $this->assertSame(4500.0, $result->data[0]['valor_sugerido']);
        $this->assertSame(1200, $result->data[1]['valor_sugerido']);
    }

    public function testExecuteDoesNotOverrideEmergencyTemplateWhenSuggestionIsZero(): void
    {
        $templates = [
            ['tipo' => Meta::TIPO_EMERGENCIA, 'titulo' => 'Reserva', 'valor_sugerido' => 1000.0],
        ];

        $service = Mockery::mock(MetaService::class);
        $service->shouldReceive('getTemplates')->once()->andReturn($templates);
        $service->shouldReceive('sugerirReservaEmergencia')->once()->with(10)->andReturn(0.0);

        $useCase = new GetMetaTemplatesUseCase($service);
        $result = $useCase->execute(10);

        $this->assertFalse($result->isError());
        $this->assertSame(1000.0, $result->data[0]['valor_sugerido']);
    }
}
