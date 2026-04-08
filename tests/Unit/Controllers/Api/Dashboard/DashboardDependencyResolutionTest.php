<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api\Dashboard;

use Application\Container\ApplicationContainer;
use Application\Controllers\Api\Dashboard\HealthController;
use Application\Controllers\Api\Dashboard\OverviewController;
use Application\Controllers\Api\Dashboard\TransactionsController;
use Application\Repositories\LancamentoRepository;
use Application\Repositories\MetaRepository;
use Application\Repositories\OrcamentoRepository;
use Application\Services\Conta\ContaService;
use Application\Services\Dashboard\DashboardHealthSummaryService;
use Application\Services\Dashboard\DashboardInsightService;
use Application\Services\Dashboard\DashboardProvisaoService;
use Application\Services\Dashboard\HealthScoreInsightService;
use Application\Services\Dashboard\HealthScoreService;
use Application\Services\Demo\DemoPreviewService;
use Application\Services\Plan\PlanLimitService;
use Illuminate\Container\Container as IlluminateContainer;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class DashboardDependencyResolutionTest extends TestCase
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

    public function testDashboardControllersResolveDependenciesFromContainerWhenAvailable(): void
    {
        $lancamentoRepo = Mockery::mock(LancamentoRepository::class);
        $provisaoService = Mockery::mock(DashboardProvisaoService::class);
        $orcamentoRepo = Mockery::mock(OrcamentoRepository::class);
        $metaRepo = Mockery::mock(MetaRepository::class);
        $healthScoreService = Mockery::mock(HealthScoreService::class);
        $dashboardInsightService = Mockery::mock(DashboardInsightService::class);
        $healthScoreInsightService = Mockery::mock(HealthScoreInsightService::class);
        $dashboardHealthSummaryService = Mockery::mock(DashboardHealthSummaryService::class);
        $planLimitService = Mockery::mock(PlanLimitService::class);
        $contaService = Mockery::mock(ContaService::class);
        $demoPreviewService = Mockery::mock(DemoPreviewService::class);

        $container = new IlluminateContainer();
        $container->instance(LancamentoRepository::class, $lancamentoRepo);
        $container->instance(DashboardProvisaoService::class, $provisaoService);
        $container->instance(OrcamentoRepository::class, $orcamentoRepo);
        $container->instance(MetaRepository::class, $metaRepo);
        $container->instance(HealthScoreService::class, $healthScoreService);
        $container->instance(DashboardInsightService::class, $dashboardInsightService);
        $container->instance(HealthScoreInsightService::class, $healthScoreInsightService);
        $container->instance(DashboardHealthSummaryService::class, $dashboardHealthSummaryService);
        $container->instance(PlanLimitService::class, $planLimitService);
        $container->instance(ContaService::class, $contaService);
        $container->instance(DemoPreviewService::class, $demoPreviewService);
        ApplicationContainer::setInstance($container);

        $overviewController = new OverviewController();
        $healthController = new HealthController();
        $transactionsController = new TransactionsController();

        foreach ([$overviewController, $healthController, $transactionsController] as $controller) {
            $this->assertSame($lancamentoRepo, $this->readProperty($controller, 'lancamentoRepo'));
            $this->assertSame($provisaoService, $this->readProperty($controller, 'provisaoService'));
            $this->assertSame($orcamentoRepo, $this->readProperty($controller, 'orcamentoRepo'));
            $this->assertSame($metaRepo, $this->readProperty($controller, 'metaRepo'));
            $this->assertSame($healthScoreService, $this->readProperty($controller, 'healthScoreService'));
            $this->assertSame($dashboardInsightService, $this->readProperty($controller, 'dashboardInsightService'));
            $this->assertSame($healthScoreInsightService, $this->readProperty($controller, 'healthScoreInsightService'));
            $this->assertSame($dashboardHealthSummaryService, $this->readProperty($controller, 'dashboardHealthSummaryService'));
            $this->assertSame($planLimitService, $this->readProperty($controller, 'planLimitService'));
            $this->assertSame($contaService, $this->readProperty($controller, 'contaService'));
            $this->assertSame($demoPreviewService, $this->readProperty($controller, 'demoPreviewService'));
        }
    }

    private function readProperty(object $object, string $property): mixed
    {
        $reflection = new \ReflectionProperty($object, $property);
        $reflection->setAccessible(true);

        return $reflection->getValue($object);
    }
}
