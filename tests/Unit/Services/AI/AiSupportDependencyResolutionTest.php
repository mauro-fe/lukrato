<?php

declare(strict_types=1);

namespace Tests\Unit\Services\AI;

use Application\Container\ApplicationContainer;
use Application\Services\AI\Context\AdminContextBuilder;
use Application\Services\AI\Context\UserContextBuilder;
use Application\Services\AI\IntentRouter;
use Application\Services\AI\Security\AIRateLimiter;
use Application\Services\AI\SystemContextService;
use Application\Services\Infrastructure\CacheService;
use Illuminate\Container\Container as IlluminateContainer;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class AiSupportDependencyResolutionTest extends TestCase
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

    public function testContextBuildersResolveSystemContextServiceFromContainerWhenAvailable(): void
    {
        $systemContextService = Mockery::mock(SystemContextService::class);

        $container = new IlluminateContainer();
        $container->instance(SystemContextService::class, $systemContextService);
        ApplicationContainer::setInstance($container);

        $userContextBuilder = new UserContextBuilder();
        $adminContextBuilder = new AdminContextBuilder();

        $this->assertSame($systemContextService, $this->readProperty($userContextBuilder, 'contextService'));
        $this->assertSame($systemContextService, $this->readProperty($adminContextBuilder, 'contextService'));
    }

    public function testIntentRouterAndRateLimiterResolveCacheFromContainerWhenAvailable(): void
    {
        $cache = Mockery::mock(CacheService::class);

        $container = new IlluminateContainer();
        $container->instance(CacheService::class, $cache);
        ApplicationContainer::setInstance($container);

        $intentRouter = new IntentRouter();
        $rateLimiter = new AIRateLimiter();
        $systemContextService = new SystemContextService();

        $this->assertSame($cache, $this->readProperty($intentRouter, 'cache'));
        $this->assertSame($cache, $this->readProperty($rateLimiter, 'cache'));
        $this->assertSame($cache, $this->readProperty($systemContextService, 'cache'));
    }

    private function readProperty(object $object, string $property): mixed
    {
        $reflection = new \ReflectionProperty($object, $property);
        $reflection->setAccessible(true);

        return $reflection->getValue($object);
    }
}
