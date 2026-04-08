<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api\Billing;

use Application\Container\ApplicationContainer;
use Application\Controllers\Api\Billing\AsaasWebhookController;
use Application\Services\Billing\AsaasService;
use Application\Services\Communication\MailService;
use Illuminate\Container\Container as IlluminateContainer;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class BillingDependencyResolutionTest extends TestCase
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

    public function testAsaasWebhookControllerResolvesDependenciesFromContainerWhenAvailable(): void
    {
        $asaasService = Mockery::mock(AsaasService::class);
        $mailService = Mockery::mock(MailService::class);

        $container = new IlluminateContainer();
        $container->instance(AsaasService::class, $asaasService);
        $container->instance(MailService::class, $mailService);
        ApplicationContainer::setInstance($container);

        $controller = new AsaasWebhookController();

        $this->assertSame($asaasService, $this->readProperty($controller, 'asaas'));
        $this->assertSame($mailService, $this->readProperty($controller, 'mailService'));
    }

    private function readProperty(object $object, string $property): mixed
    {
        $reflection = new \ReflectionProperty($object, $property);
        $reflection->setAccessible(true);

        return $reflection->getValue($object);
    }
}
