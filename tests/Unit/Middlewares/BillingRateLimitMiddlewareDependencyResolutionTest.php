<?php

declare(strict_types=1);

namespace Tests\Unit\Middlewares;

use Application\Config\RedisRuntimeConfig;
use Application\Container\ApplicationContainer;
use Application\Middlewares\BillingRateLimitMiddleware;
use Illuminate\Container\Container as IlluminateContainer;
use PHPUnit\Framework\TestCase;

class BillingRateLimitMiddlewareDependencyResolutionTest extends TestCase
{
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

    public function testConstructorResolvesRedisRuntimeConfigFromContainerWhenAvailable(): void
    {
        $runtimeConfig = new RedisRuntimeConfig();

        $container = new IlluminateContainer();
        $container->instance(RedisRuntimeConfig::class, $runtimeConfig);
        ApplicationContainer::setInstance($container);

        $middleware = new BillingRateLimitMiddleware();

        $this->assertSame($runtimeConfig, $this->readProperty($middleware, 'runtimeConfig'));
    }

    private function readProperty(object $object, string $property): mixed
    {
        $reflection = new \ReflectionProperty($object, $property);
        $reflection->setAccessible(true);

        return $reflection->getValue($object);
    }
}
