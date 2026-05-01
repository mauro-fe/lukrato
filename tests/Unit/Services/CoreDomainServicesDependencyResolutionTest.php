<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Application\Container\ApplicationContainer;
use Application\Repositories\ContaRepository;
use Application\Repositories\LancamentoRepository;
use Application\Repositories\MetaRepository;
use Application\Repositories\OrcamentoRepository;
use Application\Repositories\ParcelamentoRepository;
use Application\Services\Conta\TransferenciaService;
use Application\Services\Lancamento\LancamentoDeletionService;
use Application\Services\Lancamento\LancamentoStatusService;
use Application\Services\Metas\MetaProgressService;
use Application\Services\Metas\MetaService;
use Application\Services\Orcamentos\OrcamentoInsightService;
use Application\Services\Orcamentos\OrcamentoMetricsService;
use Application\Services\Orcamentos\OrcamentoService;
use Application\Services\Plan\PlanLimitService;
use Illuminate\Container\Container as IlluminateContainer;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class CoreDomainServicesDependencyResolutionTest extends TestCase
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

    public function testCoreDomainServicesResolveDependenciesFromContainerWhenAvailable(): void
    {
        $orcamentoRepository = Mockery::mock(OrcamentoRepository::class);
        $metaRepository = Mockery::mock(MetaRepository::class);
        $contaRepository = Mockery::mock(ContaRepository::class);
        $lancamentoRepository = Mockery::mock(LancamentoRepository::class);
        $parcelamentoRepository = Mockery::mock(ParcelamentoRepository::class);
        $planLimitService = Mockery::mock(PlanLimitService::class);
        $orcamentoMetricsService = Mockery::mock(OrcamentoMetricsService::class);
        $orcamentoInsightService = Mockery::mock(OrcamentoInsightService::class);
        $metaProgressService = Mockery::mock(MetaProgressService::class);

        $container = new IlluminateContainer();
        $container->instance(OrcamentoRepository::class, $orcamentoRepository);
        $container->instance(MetaRepository::class, $metaRepository);
        $container->instance(ContaRepository::class, $contaRepository);
        $container->instance(LancamentoRepository::class, $lancamentoRepository);
        $container->instance(ParcelamentoRepository::class, $parcelamentoRepository);
        $container->instance(PlanLimitService::class, $planLimitService);
        $container->instance(OrcamentoMetricsService::class, $orcamentoMetricsService);
        $container->instance(OrcamentoInsightService::class, $orcamentoInsightService);
        $container->instance(MetaProgressService::class, $metaProgressService);
        ApplicationContainer::setInstance($container);

        $orcamentoService = new OrcamentoService();
        $metaService = new MetaService();
        $transferenciaService = new TransferenciaService();
        $lancamentoDeletionService = new LancamentoDeletionService();
        $lancamentoStatusService = new LancamentoStatusService();

        $this->assertSame($orcamentoRepository, $this->readProperty($orcamentoService, 'repo'));
        $this->assertSame($planLimitService, $this->readProperty($orcamentoService, 'planLimit'));
        $this->assertSame($orcamentoMetricsService, $this->readProperty($orcamentoService, 'metricsService'));
        $this->assertSame($orcamentoInsightService, $this->readProperty($orcamentoService, 'insightService'));

        $this->assertSame($metaRepository, $this->readProperty($metaService, 'repo'));
        $this->assertSame($planLimitService, $this->readProperty($metaService, 'planLimit'));
        $this->assertSame($metaProgressService, $this->readProperty($metaService, 'progressService'));

        $this->assertSame($metaProgressService, $this->readProperty($transferenciaService, 'metaProgressService'));

        $this->assertSame($lancamentoRepository, $this->readProperty($lancamentoDeletionService, 'lancamentoRepo'));
        $this->assertSame($parcelamentoRepository, $this->readProperty($lancamentoDeletionService, 'parcelamentoRepo'));
        $this->assertSame($metaProgressService, $this->readProperty($lancamentoDeletionService, 'metaProgressService'));

        $this->assertSame($lancamentoRepository, $this->readProperty($lancamentoStatusService, 'lancamentoRepo'));
        $this->assertSame($metaProgressService, $this->readProperty($lancamentoStatusService, 'metaProgressService'));
    }

    private function readProperty(object $object, string $property): mixed
    {
        $reflection = new \ReflectionProperty($object, $property);
        $reflection->setAccessible(true);

        return $reflection->getValue($object);
    }
}
