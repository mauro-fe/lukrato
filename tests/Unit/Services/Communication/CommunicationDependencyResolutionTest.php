<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Communication;

use Application\Config\CommunicationRuntimeConfig;
use Application\Container\ApplicationContainer;
use Application\Services\Communication\FaturaReminderDispatchService;
use Application\Services\Communication\LancamentoReminderDispatchService;
use Application\Services\Communication\MailService;
use Application\Services\Communication\NotificationService;
use Illuminate\Container\Container as IlluminateContainer;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CommunicationDependencyResolutionTest extends TestCase
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

    public function testCommunicationServicesResolveRuntimeConfigAndLoggerFromContainerWhenAvailable(): void
    {
        $mailService = Mockery::mock(MailService::class);
        $logger = Mockery::mock(LoggerInterface::class);
        $runtimeConfig = new CommunicationRuntimeConfig();

        $container = new IlluminateContainer();
        $container->instance(MailService::class, $mailService);
        $container->instance(LoggerInterface::class, $logger);
        $container->instance(CommunicationRuntimeConfig::class, $runtimeConfig);
        ApplicationContainer::setInstance($container);

        $mail = new MailService();
        $notification = new NotificationService();
        $lancamentoReminder = new LancamentoReminderDispatchService();
        $faturaReminder = new FaturaReminderDispatchService();

        $this->assertSame($logger, $this->readProperty($mail, 'logger'));
        $this->assertSame($runtimeConfig, $this->readProperty($mail, 'runtimeConfig'));
        $this->assertSame($mailService, $this->readProperty($notification, 'mailService'));
        $this->assertSame($logger, $this->readProperty($notification, 'logger'));
        $this->assertSame($runtimeConfig, $this->readProperty($notification, 'runtimeConfig'));
        $this->assertSame($mailService, $this->readProperty($lancamentoReminder, 'mailService'));
        $this->assertSame($runtimeConfig, $this->readProperty($lancamentoReminder, 'runtimeConfig'));
        $this->assertSame($mailService, $this->readProperty($faturaReminder, 'mailService'));
        $this->assertSame($runtimeConfig, $this->readProperty($faturaReminder, 'runtimeConfig'));
    }

    private function readProperty(object $object, string $property): mixed
    {
        $reflection = new \ReflectionProperty($object, $property);
        $reflection->setAccessible(true);

        return $reflection->getValue($object);
    }
}
