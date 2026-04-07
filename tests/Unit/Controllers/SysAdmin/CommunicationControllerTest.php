<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\SysAdmin;

use Application\Container\ApplicationContainer;
use Application\Controllers\SysAdmin\CommunicationController;
use Application\Services\Admin\CommunicationAdminViewService;
use Illuminate\Container\Container as IlluminateContainer;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class CommunicationControllerTest extends TestCase
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

    public function testConstructorResolvesViewServiceFromContainerWhenAvailable(): void
    {
        $viewService = Mockery::mock(CommunicationAdminViewService::class);

        $container = new IlluminateContainer();
        $container->instance(CommunicationAdminViewService::class, $viewService);
        ApplicationContainer::setInstance($container);

        $controller = new CommunicationController();

        $this->assertSame($viewService, $this->readProperty($controller, 'viewService'));
    }

    private function readProperty(object $object, string $property): mixed
    {
        $reflection = new \ReflectionProperty($object, $property);
        $reflection->setAccessible(true);

        return $reflection->getValue($object);
    }
}
