<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Admin;

use Application\Config\AiRuntimeConfig;
use Application\Container\ApplicationContainer;
use Application\Repositories\BlogPostRepository;
use Application\Services\Admin\AiServiceHealthHttpClient;
use Application\Services\Admin\AiAdminWorkflowService;
use Application\Services\Admin\BlogAdminWorkflowService;
use Application\Services\Admin\CommunicationAdminViewService;
use Application\Services\Admin\OpenAIQuotaHttpClient;
use Application\Services\Admin\SysAdminOpsService;
use Application\Services\AI\AIService;
use Application\Services\AI\SystemContextService;
use Application\Services\Infrastructure\CacheService;
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

    public function testAiAdminAndSysAdminServicesResolveDependenciesFromContainerWhenAvailable(): void
    {
        $aiService = Mockery::mock(AIService::class);
        $systemContextService = Mockery::mock(SystemContextService::class);
        $systemContextService->shouldReceive('gather')->once()->andReturn([]);
        $cacheService = Mockery::mock(CacheService::class);
        $healthHttpClient = Mockery::mock(AiServiceHealthHttpClient::class);
        $quotaHttpClient = Mockery::mock(OpenAIQuotaHttpClient::class);
        $runtimeConfig = new AiRuntimeConfig();

        $container = new IlluminateContainer();
        $container->instance(AiRuntimeConfig::class, $runtimeConfig);
        $container->instance(AIService::class, $aiService);
        $container->instance(SystemContextService::class, $systemContextService);
        $container->instance(CacheService::class, $cacheService);
        $container->instance(AiServiceHealthHttpClient::class, $healthHttpClient);
        $container->instance(OpenAIQuotaHttpClient::class, $quotaHttpClient);
        ApplicationContainer::setInstance($container);

        $aiAdminWorkflowService = new AiAdminWorkflowService();
        $sysAdminOpsService = new SysAdminOpsService();

        $this->assertSame($aiService, $this->invokeMethod($aiAdminWorkflowService, 'createAiService'));
        $this->assertSame([], $this->invokeMethod($aiAdminWorkflowService, 'gatherSystemContext'));
        $this->assertSame($systemContextService, $this->readProperty($aiAdminWorkflowService, 'systemContextService'));
        $this->assertSame($cacheService, $this->invokeMethod($aiAdminWorkflowService, 'cache'));
        $this->assertSame($healthHttpClient, $this->invokeMethod($aiAdminWorkflowService, 'healthHttpClient'));
        $this->assertSame($quotaHttpClient, $this->invokeMethod($aiAdminWorkflowService, 'quotaHttpClient'));
        $this->assertSame($runtimeConfig, $this->readProperty($aiAdminWorkflowService, 'runtimeConfig'));
        $this->assertSame($cacheService, $this->readProperty($sysAdminOpsService, 'cacheService'));
    }

    private function readProperty(object $object, string $property): mixed
    {
        $reflection = new \ReflectionProperty($object, $property);
        $reflection->setAccessible(true);

        return $reflection->getValue($object);
    }

    private function invokeMethod(object $object, string $method): mixed
    {
        return \Closure::bind(function () use ($method) {
            return $this->{$method}();
        }, $object, $object::class)();
    }
}
