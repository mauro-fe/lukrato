<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Application\Container\ApplicationContainer;
use Application\Services\Billing\AsaasService;
use Application\Services\Billing\CustomerService;
use Application\Services\Billing\PremiumWorkflowService;
use Application\Services\Billing\WebhookQueueService;
use Application\Services\Gamification\AchievementService;
use Application\Services\Infrastructure\CircuitBreakerService;
use Application\Services\User\PerfilService;
use Application\Validators\CheckoutValidator;
use GuzzleHttp\Client;
use Illuminate\Container\Container as IlluminateContainer;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Predis\Client as RedisClient;

class BillingGatewayDependencyResolutionTest extends TestCase
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

    public function testBillingGatewayServicesResolveDependenciesFromContainerWhenAvailable(): void
    {
        $asaasService = Mockery::mock(AsaasService::class);
        $customerService = Mockery::mock(CustomerService::class);
        $validator = Mockery::mock(CheckoutValidator::class);
        $achievementService = Mockery::mock(AchievementService::class);
        $perfilService = Mockery::mock(PerfilService::class);
        $httpClient = Mockery::mock(Client::class);
        $circuitBreaker = Mockery::mock(CircuitBreakerService::class);
        $redis = Mockery::mock(RedisClient::class);
        $redis->shouldReceive('ping')->once();

        $container = new IlluminateContainer();
        $container->instance(AsaasService::class, $asaasService);
        $container->instance(CustomerService::class, $customerService);
        $container->instance(CheckoutValidator::class, $validator);
        $container->instance(AchievementService::class, $achievementService);
        $container->instance(PerfilService::class, $perfilService);
        $container->instance(Client::class, $httpClient);
        $container->instance(CircuitBreakerService::class, $circuitBreaker);
        $container->instance(RedisClient::class, $redis);
        ApplicationContainer::setInstance($container);

        $premiumWorkflowService = new PremiumWorkflowService();
        $resolvedAsaasService = new AsaasService();
        $webhookQueueService = new WebhookQueueService();
        $resolvedPerfilService = \Closure::bind(function () {
            $method = 'perfilService';

            return $this->{$method}();
        }, $premiumWorkflowService, PremiumWorkflowService::class)();

        $this->assertSame($asaasService, $this->readProperty($premiumWorkflowService, 'asaas'));
        $this->assertSame($customerService, $this->readProperty($premiumWorkflowService, 'customerService'));
        $this->assertSame($validator, $this->readProperty($premiumWorkflowService, 'validator'));
        $this->assertSame($achievementService, $this->readProperty($premiumWorkflowService, 'achievementService'));
        $this->assertSame($perfilService, $resolvedPerfilService);

        $this->assertSame($httpClient, $this->readProperty($resolvedAsaasService, 'client'));
        $this->assertSame($circuitBreaker, $this->readProperty($resolvedAsaasService, 'circuitBreaker'));
        $this->assertSame($redis, $this->readProperty($webhookQueueService, 'redis'));
    }

    private function readProperty(object $object, string $property): mixed
    {
        $reflection = new \ReflectionProperty($object, $property);
        $reflection->setAccessible(true);

        return $reflection->getValue($object);
    }
}
