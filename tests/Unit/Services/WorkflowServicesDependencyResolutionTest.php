<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Application\Builders\ReportExportBuilder;
use Application\Container\ApplicationContainer;
use Application\Core\Request;
use Application\Services\Cartao\CartaoCreditoService;
use Application\Services\Cartao\CartaoFaturaService;
use Application\Services\Communication\CampaignApiWorkflowService;
use Application\Services\Communication\NotificationApiWorkflowService;
use Application\Services\Communication\NotificationInboxService;
use Application\Services\Communication\NotificationService;
use Application\Services\Conta\ContaApiWorkflowService;
use Application\Services\Conta\ContaService;
use Application\Services\Fatura\FaturaApiWorkflowService;
use Application\Services\Fatura\FaturaService;
use Application\Services\Gamification\GamificationService;
use Application\Services\Plan\PlanLimitService;
use Application\Services\Report\ComparativesService;
use Application\Services\Report\ExcelExportService;
use Application\Services\Report\InsightsService;
use Application\Services\Report\PdfExportService;
use Application\Services\Report\ReportApiWorkflowService;
use Application\Services\Report\ReportService;
use Illuminate\Container\Container as IlluminateContainer;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class WorkflowServicesDependencyResolutionTest extends TestCase
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

    public function testNotificationWorkflowServicesResolveDependenciesFromContainerWhenAvailable(): void
    {
        $cartaoService = Mockery::mock(CartaoCreditoService::class);
        $faturaService = Mockery::mock(CartaoFaturaService::class);
        $inboxService = Mockery::mock(NotificationInboxService::class);
        $notificationService = Mockery::mock(NotificationService::class);

        $container = new IlluminateContainer();
        $container->instance(CartaoCreditoService::class, $cartaoService);
        $container->instance(CartaoFaturaService::class, $faturaService);
        $container->instance(NotificationInboxService::class, $inboxService);
        $container->instance(NotificationService::class, $notificationService);
        ApplicationContainer::setInstance($container);

        $resolvedInboxService = new NotificationInboxService();
        $notificationWorkflow = new NotificationApiWorkflowService();
        $campaignWorkflow = new CampaignApiWorkflowService();

        $this->assertSame($cartaoService, $this->readProperty($resolvedInboxService, 'cartaoService'));
        $this->assertSame($faturaService, $this->readProperty($resolvedInboxService, 'faturaService'));
        $this->assertSame($inboxService, $this->readProperty($notificationWorkflow, 'inboxService'));
        $this->assertSame($notificationService, $this->readProperty($campaignWorkflow, 'notificationService'));
    }

    public function testContaAndFaturaWorkflowServicesResolveDependenciesFromContainerWhenAvailable(): void
    {
        $contaService = Mockery::mock(ContaService::class);
        $planLimitService = Mockery::mock(PlanLimitService::class);
        $faturaService = Mockery::mock(FaturaService::class);
        $request = Mockery::mock(Request::class);

        $container = new IlluminateContainer();
        $container->instance(ContaService::class, $contaService);
        $container->instance(PlanLimitService::class, $planLimitService);
        $container->instance(FaturaService::class, $faturaService);
        $container->instance(Request::class, $request);
        ApplicationContainer::setInstance($container);

        $contaWorkflow = new ContaApiWorkflowService();
        $faturaWorkflow = new FaturaApiWorkflowService();

        $this->assertSame($contaService, $this->readProperty($contaWorkflow, 'contaService'));
        $this->assertSame($planLimitService, $this->readProperty($contaWorkflow, 'planLimitService'));
        $this->assertSame($request, $this->readProperty($contaWorkflow, 'request'));
        $this->assertSame($faturaService, $this->readProperty($faturaWorkflow, 'faturaService'));
    }

    public function testReportWorkflowServiceResolvesDependenciesFromContainerWhenAvailable(): void
    {
        $reportService = Mockery::mock(ReportService::class);
        $exportBuilder = Mockery::mock(ReportExportBuilder::class);
        $pdfExport = Mockery::mock(PdfExportService::class);
        $excelExport = Mockery::mock(ExcelExportService::class);
        $insightsService = Mockery::mock(InsightsService::class);
        $comparativesService = Mockery::mock(ComparativesService::class);
        $gamificationService = Mockery::mock(GamificationService::class);

        $container = new IlluminateContainer();
        $container->instance(ReportService::class, $reportService);
        $container->instance(ReportExportBuilder::class, $exportBuilder);
        $container->instance(PdfExportService::class, $pdfExport);
        $container->instance(ExcelExportService::class, $excelExport);
        $container->instance(InsightsService::class, $insightsService);
        $container->instance(ComparativesService::class, $comparativesService);
        $container->instance(GamificationService::class, $gamificationService);
        ApplicationContainer::setInstance($container);

        $service = new ReportApiWorkflowService();

        $this->assertSame($reportService, $this->readProperty($service, 'reportService'));
        $this->assertSame($exportBuilder, $this->readProperty($service, 'exportBuilder'));
        $this->assertSame($pdfExport, $this->readProperty($service, 'pdfExport'));
        $this->assertSame($excelExport, $this->readProperty($service, 'excelExport'));
        $this->assertSame($insightsService, $this->readProperty($service, 'insightsService'));
        $this->assertSame($comparativesService, $this->readProperty($service, 'comparativesService'));
        $this->assertSame($gamificationService, $this->readProperty($service, 'gamificationService'));
    }

    private function readProperty(object $object, string $property): mixed
    {
        $reflection = new \ReflectionProperty($object, $property);
        $reflection->setAccessible(true);

        return $reflection->getValue($object);
    }
}
