<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Application\Config\BillingRuntimeConfig;
use Application\Config\InfrastructureRuntimeConfig;
use Application\Container\ApplicationContainer;
use Application\Services\Billing\SubscriptionExpirationService;
use Application\Services\Cartao\RecorrenciaCartaoService;
use Application\Services\Communication\CampaignDeliveryStatusResolver;
use Application\Services\Communication\FaturaReminderDispatchService;
use Application\Services\Communication\LancamentoReminderDispatchService;
use Application\Services\Communication\MailService;
use Application\Services\Communication\NotificationService;
use Application\Services\Communication\ScheduledCampaignHeartbeatService;
use Application\Services\Infrastructure\CacheService;
use Application\Services\Infrastructure\SchedulerExecutionLock;
use Application\Services\Infrastructure\SchedulerTaskRunner;
use Application\Services\Lancamento\LancamentoCreationService;
use Illuminate\Container\Container as IlluminateContainer;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class ScheduledOperationsDependencyResolutionTest extends TestCase
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

    public function testScheduledOperationServicesResolveDependenciesFromContainerWhenAvailable(): void
    {
        $mailService = Mockery::mock(MailService::class);
        $logger = Mockery::mock(LoggerInterface::class);
        $deliveryStatusResolver = Mockery::mock(CampaignDeliveryStatusResolver::class);
        $notificationService = Mockery::mock(NotificationService::class);
        $cacheService = Mockery::mock(CacheService::class);
        $lock = Mockery::mock(SchedulerExecutionLock::class);
        $billingRuntimeConfig = new BillingRuntimeConfig();
        $infrastructureRuntimeConfig = new InfrastructureRuntimeConfig();
        $lancamentoReminderService = Mockery::mock(LancamentoReminderDispatchService::class);
        $faturaReminderService = Mockery::mock(FaturaReminderDispatchService::class);
        $subscriptionExpirationService = Mockery::mock(SubscriptionExpirationService::class);
        $lancamentoCreationService = Mockery::mock(LancamentoCreationService::class);
        $recorrenciaCartaoService = Mockery::mock(RecorrenciaCartaoService::class);

        $container = new IlluminateContainer();
        $container->instance(MailService::class, $mailService);
        $container->instance(LoggerInterface::class, $logger);
        $container->instance(CampaignDeliveryStatusResolver::class, $deliveryStatusResolver);
        $container->instance(NotificationService::class, $notificationService);
        $container->instance(CacheService::class, $cacheService);
        $container->instance(SchedulerExecutionLock::class, $lock);
        $container->instance(BillingRuntimeConfig::class, $billingRuntimeConfig);
        $container->instance(InfrastructureRuntimeConfig::class, $infrastructureRuntimeConfig);
        $container->instance(LancamentoReminderDispatchService::class, $lancamentoReminderService);
        $container->instance(FaturaReminderDispatchService::class, $faturaReminderService);
        $container->instance(SubscriptionExpirationService::class, $subscriptionExpirationService);
        $container->instance(LancamentoCreationService::class, $lancamentoCreationService);
        $container->instance(RecorrenciaCartaoService::class, $recorrenciaCartaoService);
        ApplicationContainer::setInstance($container);

        $notification = new NotificationService();
        $heartbeat = new ScheduledCampaignHeartbeatService();
        $runner = new SchedulerTaskRunner();
        $subscriptionExpiration = new SubscriptionExpirationService();
        $lancamentoReminder = new LancamentoReminderDispatchService();
        $faturaReminder = new FaturaReminderDispatchService();
        $mail = new MailService();

        $this->assertSame($mailService, $this->readProperty($notification, 'mailService'));
        $this->assertSame($logger, $this->readProperty($notification, 'logger'));
        $this->assertSame($deliveryStatusResolver, $this->readProperty($notification, 'deliveryStatusResolver'));
        $this->assertSame($lock, $this->readProperty($notification, 'schedulerLock'));

        $this->assertSame($notificationService, $this->readProperty($heartbeat, 'notificationService'));
        $this->assertSame($cacheService, $this->readProperty($heartbeat, 'cache'));
        $this->assertSame($lock, $this->readProperty($heartbeat, 'lock'));

        $this->assertSame($lancamentoReminderService, $this->readProperty($runner, 'lancamentoReminderService'));
        $this->assertSame($notificationService, $this->readProperty($runner, 'notificationService'));
        $this->assertSame($faturaReminderService, $this->readProperty($runner, 'faturaReminderService'));
        $this->assertSame($subscriptionExpirationService, $this->readProperty($runner, 'subscriptionExpirationService'));
        $this->assertSame($lancamentoCreationService, $this->readProperty($runner, 'lancamentoCreationService'));
        $this->assertSame($recorrenciaCartaoService, $this->readProperty($runner, 'recorrenciaCartaoService'));
        $this->assertSame($mailService, $this->readProperty($runner, 'mailService'));
        $this->assertSame($infrastructureRuntimeConfig, $this->readProperty($runner, 'runtimeConfig'));

        $this->assertSame($mailService, $this->readProperty($subscriptionExpiration, 'mail'));
        $this->assertSame($billingRuntimeConfig, $this->readProperty($subscriptionExpiration, 'runtimeConfig'));
        $this->assertSame($mailService, $this->readProperty($lancamentoReminder, 'mailService'));
        $this->assertSame($mailService, $this->readProperty($faturaReminder, 'mailService'));
        $this->assertSame($logger, $this->readProperty($mail, 'logger'));

        $mailService->shouldReceive('isConfigured')->once()->andReturn(true);

        $debug = $runner->debug();

        $this->assertTrue($debug['mail_configured']);
    }

    public function testCommunicationServicesUseNullLoggerWhenContainerHasNoLoggerBinding(): void
    {
        ApplicationContainer::flush();

        $mail = new MailService();
        $notification = new NotificationService(Mockery::mock(MailService::class));

        $this->assertInstanceOf(NullLogger::class, $this->readProperty($mail, 'logger'));
        $this->assertInstanceOf(NullLogger::class, $this->readProperty($notification, 'logger'));
    }

    private function readProperty(object $object, string $property): mixed
    {
        $reflection = new \ReflectionProperty($object, $property);
        $reflection->setAccessible(true);

        return $reflection->getValue($object);
    }
}
