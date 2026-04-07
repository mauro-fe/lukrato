<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api;

use Application\Container\ApplicationContainer;
use Application\Controllers\Api\Admin\SysAdminController;
use Application\Controllers\Api\Conta\ContasController;
use Application\Controllers\Api\Fatura\FaturasController;
use Application\Controllers\Api\Notification\CampaignController;
use Application\Controllers\Api\Notification\NotificacaoController;
use Application\Controllers\Api\Report\RelatoriosController;
use Application\Services\Admin\SysAdminOpsService;
use Application\Services\Admin\SysAdminUserService;
use Application\Services\Communication\CampaignApiWorkflowService;
use Application\Services\Communication\NotificationApiWorkflowService;
use Application\Services\Demo\DemoPreviewService;
use Application\Services\Fatura\FaturaApiWorkflowService;
use Application\Services\Conta\ContaApiWorkflowService;
use Application\Services\Report\ReportApiWorkflowService;
use Illuminate\Container\Container as IlluminateContainer;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class WorkflowControllersDependencyResolutionTest extends TestCase
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

    public function testContaAndFaturaControllersResolveWorkflowsFromContainerWhenAvailable(): void
    {
        $contaWorkflow = Mockery::mock(ContaApiWorkflowService::class);
        $demoPreviewService = Mockery::mock(DemoPreviewService::class);
        $faturaWorkflow = Mockery::mock(FaturaApiWorkflowService::class);

        $container = new IlluminateContainer();
        $container->instance(ContaApiWorkflowService::class, $contaWorkflow);
        $container->instance(DemoPreviewService::class, $demoPreviewService);
        $container->instance(FaturaApiWorkflowService::class, $faturaWorkflow);
        ApplicationContainer::setInstance($container);

        $contasController = new ContasController();
        $faturasController = new FaturasController();

        $this->assertSame($contaWorkflow, $this->readProperty($contasController, 'workflowService'));
        $this->assertSame($demoPreviewService, $this->readProperty($contasController, 'demoPreviewService'));
        $this->assertSame($faturaWorkflow, $this->readProperty($faturasController, 'workflowService'));
    }

    public function testNotificationAndCampaignControllersResolveWorkflowsFromContainerWhenAvailable(): void
    {
        $notificationWorkflow = Mockery::mock(NotificationApiWorkflowService::class);
        $campaignWorkflow = Mockery::mock(CampaignApiWorkflowService::class);

        $container = new IlluminateContainer();
        $container->instance(NotificationApiWorkflowService::class, $notificationWorkflow);
        $container->instance(CampaignApiWorkflowService::class, $campaignWorkflow);
        ApplicationContainer::setInstance($container);

        $notificationController = new NotificacaoController();
        $campaignController = new CampaignController();

        $this->assertSame($notificationWorkflow, $this->readProperty($notificationController, 'workflowService'));
        $this->assertSame($campaignWorkflow, $this->readProperty($campaignController, 'workflowService'));
    }

    public function testReportAndSysAdminControllersResolveServicesFromContainerWhenAvailable(): void
    {
        $reportWorkflow = Mockery::mock(ReportApiWorkflowService::class);
        $userService = Mockery::mock(SysAdminUserService::class);
        $opsService = Mockery::mock(SysAdminOpsService::class);

        $container = new IlluminateContainer();
        $container->instance(ReportApiWorkflowService::class, $reportWorkflow);
        $container->instance(SysAdminUserService::class, $userService);
        $container->instance(SysAdminOpsService::class, $opsService);
        ApplicationContainer::setInstance($container);

        $relatoriosController = new RelatoriosController();
        $sysAdminController = new SysAdminController();

        $this->assertSame($reportWorkflow, $this->readProperty($relatoriosController, 'workflowService'));
        $this->assertSame($userService, $this->readProperty($sysAdminController, 'userService'));
        $this->assertSame($opsService, $this->readProperty($sysAdminController, 'opsService'));
    }

    private function readProperty(object $object, string $property): mixed
    {
        $reflection = new \ReflectionProperty($object, $property);
        $reflection->setAccessible(true);

        return $reflection->getValue($object);
    }
}
