<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Dashboard;

use Application\Container\ApplicationContainer;
use Application\Repositories\LancamentoRepository;
use Application\Repositories\MetaRepository;
use Application\Repositories\OrcamentoRepository;
use Application\Services\Dashboard\DashboardHealthSummaryService;
use Application\Services\Dashboard\DashboardInsightService;
use Application\Services\Dashboard\HealthScoreInsightService;
use Application\Services\Dashboard\HealthScoreService;
use Illuminate\Container\Container as IlluminateContainer;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class DashboardServicesDependencyResolutionTest extends TestCase
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

    public function testDashboardServicesResolveDependenciesFromContainerWhenAvailable(): void
    {
        $lancamentoRepository = Mockery::mock(LancamentoRepository::class);
        $metaRepository = Mockery::mock(MetaRepository::class);
        $orcamentoRepository = Mockery::mock(OrcamentoRepository::class);
        $healthScoreService = Mockery::mock(HealthScoreService::class);
        $healthScoreInsightService = Mockery::mock(HealthScoreInsightService::class);

        $container = new IlluminateContainer();
        $container->instance(LancamentoRepository::class, $lancamentoRepository);
        $container->instance(MetaRepository::class, $metaRepository);
        $container->instance(OrcamentoRepository::class, $orcamentoRepository);
        $container->instance(HealthScoreService::class, $healthScoreService);
        $container->instance(HealthScoreInsightService::class, $healthScoreInsightService);
        ApplicationContainer::setInstance($container);

        $resolvedHealthScoreService = new HealthScoreService();
        $dashboardInsightService = new DashboardInsightService();
        $resolvedHealthScoreInsightService = new HealthScoreInsightService();
        $dashboardHealthSummaryService = new DashboardHealthSummaryService();

        $this->assertSame($lancamentoRepository, $this->readProperty($resolvedHealthScoreService, 'lancamentoRepo'));
        $this->assertSame($orcamentoRepository, $this->readProperty($resolvedHealthScoreService, 'orcamentoRepo'));
        $this->assertSame($metaRepository, $this->readProperty($resolvedHealthScoreService, 'metaRepo'));
        $this->assertSame($lancamentoRepository, $this->readProperty($dashboardInsightService, 'lancamentoRepo'));
        $this->assertSame($lancamentoRepository, $this->readProperty($resolvedHealthScoreInsightService, 'lancamentoRepo'));
        $this->assertSame($metaRepository, $this->readProperty($resolvedHealthScoreInsightService, 'metaRepo'));
        $this->assertSame($orcamentoRepository, $this->readProperty($resolvedHealthScoreInsightService, 'orcamentoRepo'));
        $this->assertSame($healthScoreService, $this->readProperty($dashboardHealthSummaryService, 'healthScoreService'));
        $this->assertSame($healthScoreInsightService, $this->readProperty($dashboardHealthSummaryService, 'healthScoreInsightService'));
    }

    private function readProperty(object $object, string $property): mixed
    {
        $reflection = new \ReflectionProperty($object, $property);
        $reflection->setAccessible(true);

        return $reflection->getValue($object);
    }
}
