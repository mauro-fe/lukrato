<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Admin;

use Application\Container\ApplicationContainer;
use Application\Repositories\BlogPostRepository;
use Application\Services\Admin\BlogAdminWorkflowService;
use Application\Services\Admin\CommunicationAdminViewService;
use Application\Services\Communication\NotificationService;
use Illuminate\Container\Container as IlluminateContainer;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class AdminDependencyResolutionTest extends TestCase
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

    public function testCommunicationAdminViewServiceResolvesNotificationServiceFromContainerWhenAvailable(): void
    {
        $notificationService = Mockery::mock(NotificationService::class);

        $container = new IlluminateContainer();
        $container->instance(NotificationService::class, $notificationService);
        ApplicationContainer::setInstance($container);

        $service = new CommunicationAdminViewService();

        $this->assertSame($notificationService, $this->readProperty($service, 'notificationService'));
    }

    public function testBlogAdminWorkflowServiceResolvesRepositoryFromContainerWhenAvailable(): void
    {
        $repository = Mockery::mock(BlogPostRepository::class);

        $container = new IlluminateContainer();
        $container->instance(BlogPostRepository::class, $repository);
        ApplicationContainer::setInstance($container);

        $service = new BlogAdminWorkflowService();

        $this->assertSame($repository, $this->readProperty($service, 'repo'));
    }

    private function readProperty(object $object, string $property): mixed
    {
        $reflection = new \ReflectionProperty($object, $property);
        $reflection->setAccessible(true);

        return $reflection->getValue($object);
    }
}
