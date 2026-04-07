<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Importacoes;

use Application\Container\ApplicationContainer;
use Application\Controllers\Admin\ImportacoesConfiguracoesController;
use Application\Controllers\Admin\ImportacoesController;
use Application\Controllers\Admin\ImportacoesHistoricoController;
use Application\Controllers\Api\Importacoes\ConfiguracoesController;
use Application\Controllers\Api\Importacoes\ConfirmController;
use Application\Controllers\Api\Importacoes\DeleteController;
use Application\Controllers\Api\Importacoes\HistoricoController;
use Application\Controllers\Api\Importacoes\JobStatusController;
use Application\Controllers\Api\Importacoes\PreviewController;
use Application\Repositories\ContaRepository;
use Application\Services\Importacao\ImportDeletionService;
use Application\Services\Importacao\ImportExecutionService;
use Application\Services\Importacao\ImportHistoryService;
use Application\Services\Importacao\ImportPreviewService;
use Application\Services\Importacao\ImportProfileConfigService;
use Application\Services\Importacao\ImportQueueService;
use Application\Services\Importacao\ImportUploadSecurityService;
use Application\Services\Plan\PlanLimitService;
use Illuminate\Container\Container as IlluminateContainer;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class ImportacoesDependencyResolutionTest extends TestCase
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

    public function testAdminImportacoesControllersResolveDependenciesFromContainerWhenAvailable(): void
    {
        $contaRepository = Mockery::mock(ContaRepository::class);
        $profileConfigService = Mockery::mock(ImportProfileConfigService::class);
        $historyService = Mockery::mock(ImportHistoryService::class);
        $planLimitService = Mockery::mock(PlanLimitService::class);

        $container = new IlluminateContainer();
        $container->instance(ContaRepository::class, $contaRepository);
        $container->instance(ImportProfileConfigService::class, $profileConfigService);
        $container->instance(ImportHistoryService::class, $historyService);
        $container->instance(PlanLimitService::class, $planLimitService);
        ApplicationContainer::setInstance($container);

        $indexController = new ImportacoesController();
        $configController = new ImportacoesConfiguracoesController();
        $historyController = new ImportacoesHistoricoController();

        $this->assertSame($contaRepository, $this->readProperty($indexController, 'contaRepository'));
        $this->assertSame($profileConfigService, $this->readProperty($indexController, 'profileConfigService'));
        $this->assertSame($historyService, $this->readProperty($indexController, 'historyService'));
        $this->assertSame($planLimitService, $this->readProperty($indexController, 'planLimitService'));
        $this->assertSame($contaRepository, $this->readProperty($configController, 'contaRepository'));
        $this->assertSame($profileConfigService, $this->readProperty($configController, 'profileConfigService'));
        $this->assertSame($contaRepository, $this->readProperty($historyController, 'contaRepository'));
        $this->assertSame($historyService, $this->readProperty($historyController, 'historyService'));
    }

    public function testApiImportacoesWorkflowControllersResolveDependenciesFromContainerWhenAvailable(): void
    {
        $executionService = Mockery::mock(ImportExecutionService::class);
        $queueService = Mockery::mock(ImportQueueService::class);
        $profileService = Mockery::mock(ImportProfileConfigService::class);
        $contaRepository = Mockery::mock(ContaRepository::class);
        $planLimitService = Mockery::mock(PlanLimitService::class);
        $uploadSecurityService = Mockery::mock(ImportUploadSecurityService::class);
        $previewService = Mockery::mock(ImportPreviewService::class);
        $deletionService = Mockery::mock(ImportDeletionService::class);

        $container = new IlluminateContainer();
        $container->instance(ImportExecutionService::class, $executionService);
        $container->instance(ImportQueueService::class, $queueService);
        $container->instance(ImportProfileConfigService::class, $profileService);
        $container->instance(ContaRepository::class, $contaRepository);
        $container->instance(PlanLimitService::class, $planLimitService);
        $container->instance(ImportUploadSecurityService::class, $uploadSecurityService);
        $container->instance(ImportPreviewService::class, $previewService);
        $container->instance(ImportDeletionService::class, $deletionService);
        ApplicationContainer::setInstance($container);

        $confirmController = new ConfirmController();
        $previewController = new PreviewController();
        $deleteController = new DeleteController();

        $this->assertSame($executionService, $this->readProperty($confirmController, 'executionService'));
        $this->assertSame($queueService, $this->readProperty($confirmController, 'queueService'));
        $this->assertSame($profileService, $this->readProperty($confirmController, 'profileService'));
        $this->assertSame($contaRepository, $this->readProperty($confirmController, 'contaRepository'));
        $this->assertSame($planLimitService, $this->readProperty($confirmController, 'planLimitService'));
        $this->assertSame($uploadSecurityService, $this->readProperty($confirmController, 'uploadSecurityService'));
        $this->assertSame($previewService, $this->readProperty($previewController, 'previewService'));
        $this->assertSame($profileService, $this->readProperty($previewController, 'profileService'));
        $this->assertSame($contaRepository, $this->readProperty($previewController, 'contaRepository'));
        $this->assertSame($planLimitService, $this->readProperty($previewController, 'planLimitService'));
        $this->assertSame($uploadSecurityService, $this->readProperty($previewController, 'uploadSecurityService'));
        $this->assertSame($deletionService, $this->readProperty($deleteController, 'deletionService'));
    }

    public function testApiImportacoesSupportControllersResolveDependenciesFromContainerWhenAvailable(): void
    {
        $profileService = Mockery::mock(ImportProfileConfigService::class);
        $contaRepository = Mockery::mock(ContaRepository::class);
        $historyService = Mockery::mock(ImportHistoryService::class);
        $queueService = Mockery::mock(ImportQueueService::class);

        $container = new IlluminateContainer();
        $container->instance(ImportProfileConfigService::class, $profileService);
        $container->instance(ContaRepository::class, $contaRepository);
        $container->instance(ImportHistoryService::class, $historyService);
        $container->instance(ImportQueueService::class, $queueService);
        ApplicationContainer::setInstance($container);

        $configController = new ConfiguracoesController();
        $historyController = new HistoricoController();
        $jobStatusController = new JobStatusController();

        $this->assertSame($profileService, $this->readProperty($configController, 'profileService'));
        $this->assertSame($contaRepository, $this->readProperty($configController, 'contaRepository'));
        $this->assertSame($historyService, $this->readProperty($historyController, 'historyService'));
        $this->assertSame($queueService, $this->readProperty($jobStatusController, 'queueService'));
    }

    private function readProperty(object $object, string $property): mixed
    {
        $reflection = new \ReflectionProperty($object, $property);
        $reflection->setAccessible(true);

        return $reflection->getValue($object);
    }
}
