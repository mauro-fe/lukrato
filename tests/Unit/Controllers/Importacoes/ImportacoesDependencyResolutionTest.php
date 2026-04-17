<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Importacoes;

use Application\Container\ApplicationContainer;
use Application\Controllers\Admin\ImportacoesConfiguracoesController;
use Application\Controllers\Admin\ImportacoesController;
use Application\Controllers\Admin\ImportacoesHistoricoController;
use Application\Controllers\Api\Importacoes\ConfiguracoesPageInitController;
use Application\Controllers\Api\Importacoes\ConfiguracoesController;
use Application\Controllers\Api\Importacoes\ConfirmController;
use Application\Controllers\Api\Importacoes\DeleteController;
use Application\Controllers\Api\Importacoes\HistoricoController;
use Application\Controllers\Api\Importacoes\HistoricoPageInitController;
use Application\Controllers\Api\Importacoes\JobStatusController;
use Application\Controllers\Api\Importacoes\PageInitController;
use Application\Controllers\Api\Importacoes\PreviewController;
use Application\Services\Importacao\ImportacoesConfiguracoesPageDataService;
use Application\Services\Importacao\ImportacoesHistoricoPageDataService;
use Application\Repositories\ContaRepository;
use Application\Services\Importacao\ImportDeletionService;
use Application\Services\Importacao\ImportExecutionService;
use Application\Services\Importacao\ImportHistoryService;
use Application\Services\Importacao\ImportacoesIndexPageDataService;
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
        $pageDataService = Mockery::mock(ImportacoesIndexPageDataService::class);
        $configPageDataService = Mockery::mock(ImportacoesConfiguracoesPageDataService::class);
        $historyPageDataService = Mockery::mock(ImportacoesHistoricoPageDataService::class);

        $container = new IlluminateContainer();
        $container->instance(ImportacoesIndexPageDataService::class, $pageDataService);
        $container->instance(ImportacoesConfiguracoesPageDataService::class, $configPageDataService);
        $container->instance(ImportacoesHistoricoPageDataService::class, $historyPageDataService);
        ApplicationContainer::setInstance($container);

        $indexController = new ImportacoesController();
        $configController = new ImportacoesConfiguracoesController();
        $historyController = new ImportacoesHistoricoController();

        $this->assertSame($pageDataService, $this->readProperty($indexController, 'pageDataService'));
        $this->assertSame($configPageDataService, $this->readProperty($configController, 'pageDataService'));
        $this->assertSame($historyPageDataService, $this->readProperty($historyController, 'pageDataService'));
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
        $pageDataService = Mockery::mock(ImportacoesIndexPageDataService::class);
        $configPageDataService = Mockery::mock(ImportacoesConfiguracoesPageDataService::class);
        $historyPageDataService = Mockery::mock(ImportacoesHistoricoPageDataService::class);
        $profileService = Mockery::mock(ImportProfileConfigService::class);
        $contaRepository = Mockery::mock(ContaRepository::class);
        $historyService = Mockery::mock(ImportHistoryService::class);
        $queueService = Mockery::mock(ImportQueueService::class);

        $container = new IlluminateContainer();
        $container->instance(ImportacoesIndexPageDataService::class, $pageDataService);
        $container->instance(ImportacoesConfiguracoesPageDataService::class, $configPageDataService);
        $container->instance(ImportacoesHistoricoPageDataService::class, $historyPageDataService);
        $container->instance(ImportProfileConfigService::class, $profileService);
        $container->instance(ContaRepository::class, $contaRepository);
        $container->instance(ImportHistoryService::class, $historyService);
        $container->instance(ImportQueueService::class, $queueService);
        ApplicationContainer::setInstance($container);

        $pageInitController = new PageInitController();
        $configPageInitController = new ConfiguracoesPageInitController();
        $configController = new ConfiguracoesController();
        $historyPageInitController = new HistoricoPageInitController();
        $historyController = new HistoricoController();
        $jobStatusController = new JobStatusController();

        $this->assertSame($pageDataService, $this->readProperty($pageInitController, 'pageDataService'));
        $this->assertSame($configPageDataService, $this->readProperty($configPageInitController, 'pageDataService'));
        $this->assertSame($profileService, $this->readProperty($configController, 'profileService'));
        $this->assertSame($contaRepository, $this->readProperty($configController, 'contaRepository'));
        $this->assertSame($historyPageDataService, $this->readProperty($historyPageInitController, 'pageDataService'));
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
