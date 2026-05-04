<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Gamification;

use Application\Enums\GamificationAction;
use Application\Services\Gamification\AchievementService;
use Application\Services\Gamification\GamificationService;
use Application\Services\Gamification\LevelService;
use Application\Services\Gamification\StreakService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class GamificationServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testAddPointsReturnsRichPaidPlanMetadata(): void
    {
        $userModel = Mockery::mock('alias:Application\Models\Usuario');
        $userModel->shouldReceive('find')->once()->with(501)->andReturn(new class {
            public function plan(): object
            {
                return new class {
                    public function summary(string $tierKey = 'plan'): array
                    {
                        return [
                            $tierKey => 'ultra',
                            'is_pro' => true,
                            'is_ultra' => true,
                            'plan_label' => 'ULTRA',
                            'upgrade_target' => null,
                        ];
                    }
                };
            }
        });

        $userProgressModel = Mockery::mock('alias:Application\Models\UserProgress');
        $userProgressModel->total_points = 100;
        $userProgressModel->shouldReceive('save')->once()->andReturnTrue();
        $userProgressModel
            ->shouldReceive('firstOrCreate')
            ->once()
            ->with(['user_id' => 501], Mockery::type('array'))
            ->andReturn($userProgressModel);

        $pointsLogModel = Mockery::mock('alias:Application\Models\PointsLog');
        $pointsLogModel
            ->shouldReceive('create')
            ->once()
            ->with(Mockery::on(static function (array $payload): bool {
                return $payload['user_id'] === 501
                    && $payload['points'] === 15
                    && $payload['metadata']['plan'] === 'ultra'
                    && $payload['metadata']['is_pro'] === true
                    && $payload['metadata']['is_ultra'] === true
                    && $payload['metadata']['plan_label'] === 'ULTRA'
                    && $payload['metadata']['upgrade_target'] === null
                    && $payload['metadata']['base_points'] === 15;
            }))
            ->andReturnTrue();

        $achievementService = Mockery::mock(AchievementService::class);
        $achievementService
            ->shouldReceive('checkAndUnlockAchievements')
            ->once()
            ->with(501, GamificationAction::CREATE_LANCAMENTO->value)
            ->andReturn([]);

        $levelService = Mockery::mock(LevelService::class);
        $levelService
            ->shouldReceive('recalculateLevel')
            ->once()
            ->with(501)
            ->andReturn([
                'current_level' => 2,
                'level_up' => false,
                'progress_percentage' => 45,
            ]);

        $service = new GamificationService(
            $achievementService,
            Mockery::mock(StreakService::class),
            $levelService,
        );

        $result = $service->addPoints(501, GamificationAction::CREATE_LANCAMENTO);

        $this->assertTrue($result['success']);
        $this->assertSame(15, $result['points_gained']);
        $this->assertSame('ultra', $result['plan']);
        $this->assertTrue($result['is_pro']);
        $this->assertTrue($result['is_ultra']);
        $this->assertSame('ULTRA', $result['plan_label']);
        $this->assertNull($result['upgrade_target']);
        $this->assertSame(115, $result['total_points']);
        $userProgressModel->shouldHaveReceived('save')->once();
    }

    public function testAddPointsFallsBackToFreePlanMetadataWhenUserCannotBeLoaded(): void
    {
        $userModel = Mockery::mock('alias:Application\Models\Usuario');
        $userModel->shouldReceive('find')->once()->with(502)->andReturn(null);

        $userProgressModel = Mockery::mock('alias:Application\Models\UserProgress');
        $userProgressModel->total_points = 0;
        $userProgressModel->shouldReceive('save')->once()->andReturnTrue();
        $userProgressModel
            ->shouldReceive('firstOrCreate')
            ->once()
            ->with(['user_id' => 502], Mockery::type('array'))
            ->andReturn($userProgressModel);

        $pointsLogModel = Mockery::mock('alias:Application\Models\PointsLog');
        $pointsLogModel
            ->shouldReceive('create')
            ->once()
            ->with(Mockery::on(static function (array $payload): bool {
                return $payload['points'] === 10
                    && $payload['metadata']['plan'] === 'free'
                    && $payload['metadata']['is_pro'] === false
                    && $payload['metadata']['is_ultra'] === false
                    && $payload['metadata']['plan_label'] === 'FREE'
                    && $payload['metadata']['upgrade_target'] === 'pro';
            }))
            ->andReturnTrue();

        $achievementService = Mockery::mock(AchievementService::class);
        $achievementService
            ->shouldReceive('checkAndUnlockAchievements')
            ->once()
            ->with(502, GamificationAction::CREATE_LANCAMENTO->value)
            ->andReturn([]);

        $levelService = Mockery::mock(LevelService::class);
        $levelService
            ->shouldReceive('recalculateLevel')
            ->once()
            ->with(502)
            ->andReturn([
                'current_level' => 1,
                'level_up' => false,
                'progress_percentage' => 3,
            ]);

        $service = new GamificationService(
            $achievementService,
            Mockery::mock(StreakService::class),
            $levelService,
        );

        $result = $service->addPoints(502, GamificationAction::CREATE_LANCAMENTO);

        $this->assertTrue($result['success']);
        $this->assertSame(10, $result['points_gained']);
        $this->assertSame('free', $result['plan']);
        $this->assertFalse($result['is_pro']);
        $this->assertFalse($result['is_ultra']);
        $this->assertSame('FREE', $result['plan_label']);
        $this->assertSame('pro', $result['upgrade_target']);
        $this->assertSame(10, $result['total_points']);
    }
}
