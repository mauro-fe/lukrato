<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Application\Config\AsaasRuntimeConfig;
use Application\Config\RedisRuntimeConfig;
use Application\Container\ApplicationContainer;
use Application\Core\Request;
use Application\Services\Billing\AsaasService;
use Application\Services\Billing\AsaasCircuitBreakerService;
use Application\Services\Billing\AsaasHttpClient;
use Application\Services\Billing\CustomerService;
use Application\Services\Billing\PremiumWorkflowService;
use Application\Services\Billing\WebhookQueueService;
use Application\Services\Gamification\AchievementService;
use Application\Services\Infrastructure\CircuitBreakerService;
use Application\Services\User\PerfilService;
use Application\Validators\CheckoutValidator;
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
        $httpClient = Mockery::mock(AsaasHttpClient::class);
        $circuitBreaker = Mockery::mock(AsaasCircuitBreakerService::class);
        $runtimeConfig = new AsaasRuntimeConfig();
        $redisRuntimeConfig = new RedisRuntimeConfig();
        $redis = Mockery::mock(RedisClient::class);
        $redis->shouldReceive('ping')->once();

        $container = new IlluminateContainer();
        $container->instance(AsaasService::class, $asaasService);
        $container->instance(CustomerService::class, $customerService);
        $container->instance(CheckoutValidator::class, $validator);
        $container->instance(AchievementService::class, $achievementService);
        $container->instance(PerfilService::class, $perfilService);
        $container->instance(AsaasHttpClient::class, $httpClient);
        $container->instance(AsaasCircuitBreakerService::class, $circuitBreaker);
        $container->instance(AsaasRuntimeConfig::class, $runtimeConfig);
        $container->instance(RedisRuntimeConfig::class, $redisRuntimeConfig);
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
        $this->assertSame($runtimeConfig, $this->readProperty($resolvedAsaasService, 'runtimeConfig'));
        $this->assertSame($redis, $this->readProperty($webhookQueueService, 'redis'));
        $this->assertSame($redisRuntimeConfig, $this->readProperty($webhookQueueService, 'runtimeConfig'));
    }

    public function testAsaasServiceFallsBackToNamedCircuitBreakerWhenContainerDoesNotProvideOne(): void
    {
        $httpClient = Mockery::mock(AsaasHttpClient::class);

        $container = new IlluminateContainer();
        $container->instance(AsaasHttpClient::class, $httpClient);
        ApplicationContainer::setInstance($container);

        $service = new AsaasService();

        $this->assertInstanceOf(AsaasCircuitBreakerService::class, $this->readProperty($service, 'circuitBreaker'));
    }

    public function testAsaasServiceUsesRequestForTrustedProxyWebhookIpResolution(): void
    {
        $previousTrustedProxies = $_ENV['TRUSTED_PROXIES'] ?? null;
        $_ENV['TRUSTED_PROXIES'] = '127.0.0.10';

        try {
            $httpClient = Mockery::mock(AsaasHttpClient::class);
            $request = new Request(server: [
                'REQUEST_METHOD' => 'POST',
                'REMOTE_ADDR' => '127.0.0.10',
                'HTTP_X_FORWARDED_FOR' => '10.1.2.3, 10.1.2.4',
            ]);

            $container = new IlluminateContainer();
            $container->instance(AsaasHttpClient::class, $httpClient);
            $container->instance(Request::class, $request);
            ApplicationContainer::setInstance($container);

            $service = new AsaasService();

            $this->assertSame('10.1.2.3', $this->invokePrivateMethod($service, 'getClientIp'));
        } finally {
            if ($previousTrustedProxies === null) {
                unset($_ENV['TRUSTED_PROXIES']);
            } else {
                $_ENV['TRUSTED_PROXIES'] = (string) $previousTrustedProxies;
            }
        }
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
