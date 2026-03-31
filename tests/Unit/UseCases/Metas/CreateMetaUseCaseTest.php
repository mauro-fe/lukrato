<?php

declare(strict_types=1);

namespace Tests\Unit\UseCases\Metas;

use Application\Services\Financeiro\MetaService;
use Application\Services\Gamification\AchievementService;
use Application\UseCases\Metas\CreateMetaUseCase;
use DomainException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class CreateMetaUseCaseTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testExecuteReturnsValidationFailWhenPayloadIsInvalid(): void
    {
        $metaService = Mockery::mock(MetaService::class);
        $achievementService = Mockery::mock(AchievementService::class);

        $metaService->shouldNotReceive('criar');
        $achievementService->shouldNotReceive('checkAndUnlockAchievements');

        $useCase = new CreateMetaUseCase($metaService, $achievementService);
        $result = $useCase->execute(10, []);

        $this->assertTrue($result->isValidationError());
        $this->assertSame(422, $result->httpCode);
        $this->assertArrayHasKey('titulo', $result->data['errors'] ?? []);
        $this->assertArrayHasKey('valor_alvo', $result->data['errors'] ?? []);
    }

    public function testExecuteReturns403WhenMetaServiceThrowsDomainException(): void
    {
        $metaService = Mockery::mock(MetaService::class);
        $achievementService = Mockery::mock(AchievementService::class);

        $metaService->shouldReceive('criar')
            ->once()
            ->with(10, Mockery::type('array'))
            ->andThrow(new DomainException('Limite de metas atingido.'));
        $achievementService->shouldNotReceive('checkAndUnlockAchievements');

        $useCase = new CreateMetaUseCase($metaService, $achievementService);
        $result = $useCase->execute(10, [
            'titulo' => 'Reserva',
            'valor_alvo' => 1000,
        ]);

        $this->assertTrue($result->isError());
        $this->assertSame(403, $result->httpCode);
        $this->assertSame('Limite de metas atingido.', $result->message);
    }

    public function testExecuteReturnsCreatedMetaWithGamificationWhenAchievementsAreUnlocked(): void
    {
        $metaService = Mockery::mock(MetaService::class);
        $achievementService = Mockery::mock(AchievementService::class);

        $metaPayload = [
            'id' => 12,
            'titulo' => 'Reserva de Emergencia',
        ];
        $achievements = [
            ['code' => 'meta_criada'],
        ];

        $metaService->shouldReceive('criar')
            ->once()
            ->with(10, Mockery::type('array'))
            ->andReturn($metaPayload);
        $achievementService->shouldReceive('checkAndUnlockAchievements')
            ->once()
            ->with(10, 'meta_criada')
            ->andReturn($achievements);

        $useCase = new CreateMetaUseCase($metaService, $achievementService);
        $result = $useCase->execute(10, [
            'titulo' => 'Reserva de Emergencia',
            'valor_alvo' => 5000,
        ]);

        $this->assertFalse($result->isError());
        $this->assertSame(201, $result->httpCode);
        $this->assertSame('Meta criada com sucesso!', $result->message);
        $this->assertSame($metaPayload, $result->data['meta'] ?? null);
        $this->assertSame($achievements, $result->data['gamification']['achievements'] ?? null);
    }
}
