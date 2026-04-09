<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Referral;

use Application\Config\ReferralRuntimeConfig;
use Application\Container\ApplicationContainer;
use Application\Services\Referral\ReferralAntifraudService;
use Illuminate\Container\Container as IlluminateContainer;
use PHPUnit\Framework\TestCase;

class ReferralDependencyResolutionTest extends TestCase
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

    public function testReferralAntifraudServiceResolvesRuntimeConfigFromContainerWhenAvailable(): void
    {
        $runtimeConfig = new ReferralRuntimeConfig();

        $container = new IlluminateContainer();
        $container->instance(ReferralRuntimeConfig::class, $runtimeConfig);
        ApplicationContainer::setInstance($container);

        $service = new ReferralAntifraudService();

        $this->assertSame($runtimeConfig, $this->readProperty($service, 'runtimeConfig'));
    }

    private function readProperty(object $object, string $property): mixed
    {
        $reflection = new \ReflectionProperty($object, $property);
        $reflection->setAccessible(true);

        return $reflection->getValue($object);
    }
}
