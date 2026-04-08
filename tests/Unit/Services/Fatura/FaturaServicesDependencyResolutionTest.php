<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Fatura;

use Application\Container\ApplicationContainer;
use Application\Services\Fatura\FaturaCancellationService;
use Application\Services\Fatura\FaturaCreationService;
use Application\Services\Fatura\FaturaFormatterService;
use Application\Services\Fatura\FaturaInstallmentCalculatorService;
use Application\Services\Fatura\FaturaItemManagementService;
use Application\Services\Fatura\FaturaItemPaymentService;
use Application\Services\Fatura\FaturaItemPaymentStateService;
use Application\Services\Fatura\FaturaReadService;
use Application\Services\Fatura\FaturaService;
use Illuminate\Container\Container as IlluminateContainer;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class FaturaServicesDependencyResolutionTest extends TestCase
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

    public function testFaturaServicesResolveDependenciesFromContainerWhenAvailable(): void
    {
        $calculatorService = Mockery::mock(FaturaInstallmentCalculatorService::class);
        $formatterService = Mockery::mock(FaturaFormatterService::class);
        $itemPaymentStateService = Mockery::mock(FaturaItemPaymentStateService::class);
        $creationService = Mockery::mock(FaturaCreationService::class);
        $itemManagementService = Mockery::mock(FaturaItemManagementService::class);
        $readService = Mockery::mock(FaturaReadService::class);
        $cancellationService = Mockery::mock(FaturaCancellationService::class);
        $itemPaymentService = Mockery::mock(FaturaItemPaymentService::class);

        $container = new IlluminateContainer();
        $container->instance(FaturaInstallmentCalculatorService::class, $calculatorService);
        $container->instance(FaturaFormatterService::class, $formatterService);
        $container->instance(FaturaItemPaymentStateService::class, $itemPaymentStateService);
        $container->instance(FaturaCreationService::class, $creationService);
        $container->instance(FaturaItemManagementService::class, $itemManagementService);
        $container->instance(FaturaReadService::class, $readService);
        $container->instance(FaturaCancellationService::class, $cancellationService);
        $container->instance(FaturaItemPaymentService::class, $itemPaymentService);
        ApplicationContainer::setInstance($container);

        $faturaService = new FaturaService();
        $faturaReadService = new FaturaReadService();
        $faturaItemPaymentService = new FaturaItemPaymentService();
        $faturaCreationService = new FaturaCreationService();

        $this->assertSame($readService, $this->readProperty($faturaService, 'readService'));
        $this->assertSame($itemPaymentService, $this->readProperty($faturaService, 'itemPaymentService'));
        $this->assertSame($creationService, $this->readProperty($faturaService, 'creationService'));
        $this->assertSame($itemManagementService, $this->readProperty($faturaService, 'itemManagementService'));
        $this->assertSame($cancellationService, $this->readProperty($faturaService, 'cancellationService'));

        $this->assertSame($formatterService, $this->readProperty($faturaReadService, 'formatterService'));
        $this->assertSame($itemPaymentStateService, $this->readProperty($faturaItemPaymentService, 'itemPaymentStateService'));
        $this->assertSame($calculatorService, $this->readProperty($faturaCreationService, 'calculatorService'));
    }

    private function readProperty(object $object, string $property): mixed
    {
        $reflection = new \ReflectionProperty($object, $property);
        $reflection->setAccessible(true);

        return $reflection->getValue($object);
    }
}
