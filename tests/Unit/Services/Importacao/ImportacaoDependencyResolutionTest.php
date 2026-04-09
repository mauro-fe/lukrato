<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Importacao;

use Application\Config\ImportacaoRuntimeConfig;
use Application\Container\ApplicationContainer;
use Application\Services\Cartao\CartaoBillingDateService;
use Application\Services\Cartao\CartaoFaturaSupportService;
use Application\Services\Importacao\ImportDeletionService;
use Application\Services\Importacao\ImportExecutionService;
use Application\Services\Importacao\ImportHistoryService;
use Application\Services\Importacao\ImportPreviewService;
use Application\Services\Importacao\ImportQueueService;
use Application\Services\Lancamento\LancamentoCreationService;
use Illuminate\Container\Container as IlluminateContainer;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class ImportacaoDependencyResolutionTest extends TestCase
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

    public function testImportExecutionServiceResolvesDependenciesFromContainerWhenAvailable(): void
    {
        $previewService = Mockery::mock(ImportPreviewService::class);
        $creationService = Mockery::mock(LancamentoCreationService::class);
        $billingDateService = Mockery::mock(CartaoBillingDateService::class);
        $faturaSupportService = Mockery::mock(CartaoFaturaSupportService::class);

        $container = new IlluminateContainer();
        $container->instance(ImportPreviewService::class, $previewService);
        $container->instance(LancamentoCreationService::class, $creationService);
        $container->instance(CartaoBillingDateService::class, $billingDateService);
        $container->instance(CartaoFaturaSupportService::class, $faturaSupportService);
        ApplicationContainer::setInstance($container);

        $service = new ImportExecutionService();

        $this->assertSame($previewService, $this->readProperty($service, 'previewService'));
        $this->assertSame($creationService, $this->readProperty($service, 'lancamentoCreationService'));
        $this->assertSame($billingDateService, $this->readProperty($service, 'billingDateService'));
        $this->assertSame($faturaSupportService, $this->readProperty($service, 'faturaSupportService'));
    }

    public function testImportQueueServiceResolvesExecutionServiceFromContainerWhenAvailable(): void
    {
        $executionService = Mockery::mock(ImportExecutionService::class);
        $runtimeConfig = new ImportacaoRuntimeConfig();

        $container = new IlluminateContainer();
        $container->instance(ImportExecutionService::class, $executionService);
        $container->instance(ImportacaoRuntimeConfig::class, $runtimeConfig);
        ApplicationContainer::setInstance($container);

        $service = new ImportQueueService();

        $this->assertSame($executionService, $this->readProperty($service, 'executionService'));
        $this->assertSame($runtimeConfig, $this->readProperty($service, 'runtimeConfig'));
    }

    public function testImportDeletionServiceResolvesDependenciesFromContainerWhenAvailable(): void
    {
        $historyService = Mockery::mock(ImportHistoryService::class);
        $billingDateService = Mockery::mock(CartaoBillingDateService::class);

        $container = new IlluminateContainer();
        $container->instance(ImportHistoryService::class, $historyService);
        $container->instance(CartaoBillingDateService::class, $billingDateService);
        ApplicationContainer::setInstance($container);

        $service = new ImportDeletionService();

        $this->assertSame($historyService, $this->readProperty($service, 'historyService'));
        $this->assertSame($billingDateService, $this->readProperty($service, 'billingDateService'));
    }

    private function readProperty(object $object, string $property): mixed
    {
        $reflection = new \ReflectionProperty($object, $property);
        $reflection->setAccessible(true);

        return $reflection->getValue($object);
    }
}
