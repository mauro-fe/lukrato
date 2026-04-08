<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Application\Container\ApplicationContainer;
use Application\Repositories\FeedbackRepository;
use Application\Services\Feedback\FeedbackService;
use Application\Services\Gamification\AchievementService;
use Application\Services\Gamification\GamificationService;
use Application\Services\Gamification\LevelService;
use Application\Services\Gamification\StreakService;
use Application\Services\Referral\ReferralAntifraudService;
use Application\Services\Referral\ReferralService;
use Illuminate\Container\Container as IlluminateContainer;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class EngagementServicesDependencyResolutionTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();
        ApplicationContainer::flush();
    }

    protected function tearDown(): void
    {
        ApplicationContainer::flush();
        parent::tearDown();
    }

    public function testEngagementServicesResolveDependenciesFromContainerWhenAvailable(): void
    {
        $achievementService = Mockery::mock(AchievementService::class);
        $streakService = Mockery::mock(StreakService::class);
        $levelService = Mockery::mock(LevelService::class);
        $feedbackRepository = Mockery::mock(FeedbackRepository::class);
        $antifraudService = Mockery::mock(ReferralAntifraudService::class);

        $container = new IlluminateContainer();
        $container->instance(AchievementService::class, $achievementService);
        $container->instance(StreakService::class, $streakService);
        $container->instance(LevelService::class, $levelService);
        $container->instance(FeedbackRepository::class, $feedbackRepository);
        $container->instance(ReferralAntifraudService::class, $antifraudService);
        ApplicationContainer::setInstance($container);

        $resolvedGamificationService = new GamificationService();
        $resolvedAchievementService = new AchievementService();
        $feedbackService = new FeedbackService();
        $referralService = new ReferralService();

        $this->assertSame($achievementService, $this->readProperty($resolvedGamificationService, 'achievementService'));
        $this->assertSame($streakService, $this->readProperty($resolvedGamificationService, 'streakService'));
        $this->assertSame($levelService, $this->readProperty($resolvedGamificationService, 'levelService'));
        $this->assertSame($levelService, $this->readProperty($resolvedAchievementService, 'levelService'));
        $this->assertSame($feedbackRepository, $this->readProperty($feedbackService, 'repo'));
        $this->assertSame($antifraudService, $this->invokePrivateMethod($referralService, 'antifraudService'));
        $this->assertSame($achievementService, $this->invokePrivateMethod($referralService, 'achievementService'));
    }

    private function readProperty(object $object, string $property): mixed
    {
        $reflection = new \ReflectionProperty($object, $property);
        $reflection->setAccessible(true);

        return $reflection->getValue($object);
    }

    private function invokePrivateMethod(object $object, string $method): mixed
    {
        return \Closure::bind(function () use ($method) {
            return $this->{$method}();
        }, $object, $object::class)();
    }
}
