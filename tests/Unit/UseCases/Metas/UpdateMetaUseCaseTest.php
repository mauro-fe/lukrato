<?php

declare(strict_types=1);

namespace Tests\Unit\UseCases\Metas;

use Application\Services\Metas\MetaService;
use Application\UseCases\Metas\UpdateMetaUseCase;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class UpdateMetaUseCaseTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testExecuteReturnsValidationFailWhenPayloadIsInvalid(): void
    {
        $metaService = Mockery::mock(MetaService::class);
        $metaService->shouldNotReceive('atualizar');

        $useCase = new UpdateMetaUseCase($metaService);
        $result = $useCase->execute(10, 5, [
            'titulo' => '',
        ]);

        $this->assertTrue($result->isValidationError());
        $this->assertSame(422, $result->httpCode);
        $this->assertArrayHasKey('titulo', $result->data['errors'] ?? []);
    }

    public function testExecuteReturnsNotFoundWhenMetaDoesNotExist(): void
    {
        $metaService = Mockery::mock(MetaService::class);
        $metaService->shouldReceive('atualizar')
            ->once()
            ->with(10, 99, [])
            ->andReturnNull();

        $useCase = new UpdateMetaUseCase($metaService);
        $result = $useCase->execute(10, 99, []);

        $this->assertTrue($result->isError());
        $this->assertSame(404, $result->httpCode);
        $this->assertSame('Meta não encontrada.', $result->message);
    }

    public function testExecuteReturnsUpdatedMetaPayloadOnSuccess(): void
    {
        $metaPayload = [
            'id' => 7,
            'titulo' => 'Nova Meta',
        ];

        $metaService = Mockery::mock(MetaService::class);
        $metaService->shouldReceive('atualizar')
            ->once()
            ->with(10, 7, ['titulo' => 'Nova Meta'])
            ->andReturn($metaPayload);

        $useCase = new UpdateMetaUseCase($metaService);
        $result = $useCase->execute(10, 7, ['titulo' => 'Nova Meta']);

        $this->assertFalse($result->isError());
        $this->assertSame(200, $result->httpCode);
        $this->assertSame('Meta atualizada com sucesso!', $result->message);
        $this->assertSame($metaPayload, $result->data);
    }
}
