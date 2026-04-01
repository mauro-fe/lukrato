<?php

declare(strict_types=1);

namespace Tests\Unit\UseCases\Metas;

use Application\Services\Metas\MetaService;
use Application\Services\Gamification\AchievementService;
use Application\UseCases\Metas\AddMetaAporteUseCase;
use DomainException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class AddMetaAporteUseCaseTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testExecuteReturnsValidationFailWhenPayloadIsInvalid(): void
    {
        $metaService = Mockery::mock(MetaService::class);
        $achievementService = Mockery::mock(AchievementService::class);

        $metaService->shouldNotReceive('adicionarAporte');
        $achievementService->shouldNotReceive('checkAndUnlockAchievements');

        $useCase = new AddMetaAporteUseCase($metaService, $achievementService);
        $result = $useCase->execute(10, 1, []);

        $this->assertTrue($result->isValidationError());
        $this->assertSame(422, $result->httpCode);
        $this->assertArrayHasKey('valor', $result->data['errors'] ?? []);
    }

    public function testExecuteReturns400WhenMetaServiceThrowsDomainException(): void
    {
        $metaService = Mockery::mock(MetaService::class);
        $achievementService = Mockery::mock(AchievementService::class);

        $metaService->shouldReceive('adicionarAporte')
            ->once()
            ->with(10, 1, 100.0)
            ->andThrow(new DomainException('Aporte manual em meta foi descontinuado.'));
        $achievementService->shouldNotReceive('checkAndUnlockAchievements');

        $useCase = new AddMetaAporteUseCase($metaService, $achievementService);
        $result = $useCase->execute(10, 1, ['valor' => 100]);

        $this->assertTrue($result->isError());
        $this->assertSame(400, $result->httpCode);
        $this->assertSame('Aporte manual em meta foi descontinuado.', $result->message);
    }

    public function testExecuteReturns404WhenMetaDoesNotExist(): void
    {
        $metaService = Mockery::mock(MetaService::class);
        $achievementService = Mockery::mock(AchievementService::class);

        $metaService->shouldReceive('adicionarAporte')
            ->once()
            ->with(10, 99, 100.0)
            ->andReturnNull();
        $achievementService->shouldNotReceive('checkAndUnlockAchievements');

        $useCase = new AddMetaAporteUseCase($metaService, $achievementService);
        $result = $useCase->execute(10, 99, ['valor' => 100]);

        $this->assertTrue($result->isError());
        $this->assertSame(404, $result->httpCode);
        $this->assertSame('Meta não encontrada.', $result->message);
    }

    public function testExecuteReturnsSuccessWithGamificationWhenAchievementUnlocks(): void
    {
        $metaService = Mockery::mock(MetaService::class);
        $achievementService = Mockery::mock(AchievementService::class);

        $metaPayload = ['id' => 4, 'titulo' => 'Reserva'];
        $achievements = [['code' => 'meta_aporte']];

        $metaService->shouldReceive('adicionarAporte')
            ->once()
            ->with(10, 4, 200.0)
            ->andReturn($metaPayload);
        $achievementService->shouldReceive('checkAndUnlockAchievements')
            ->once()
            ->with(10, 'meta_aporte')
            ->andReturn($achievements);

        $useCase = new AddMetaAporteUseCase($metaService, $achievementService);
        $result = $useCase->execute(10, 4, ['valor' => 200]);

        $this->assertFalse($result->isError());
        $this->assertSame(200, $result->httpCode);
        $this->assertSame('Aporte registrado com sucesso!', $result->message);
        $this->assertSame($metaPayload, $result->data['meta'] ?? null);
        $this->assertSame($achievements, $result->data['gamification']['achievements'] ?? null);
    }
}
