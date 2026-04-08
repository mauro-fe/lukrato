<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Application\Container\ApplicationContainer;
use Application\Repositories\DocumentoRepository;
use Application\Repositories\LancamentoRepository;
use Application\Repositories\MetaRepository;
use Application\Repositories\OrcamentoRepository;
use Application\Repositories\ReportRepository;
use Application\Services\Billing\CustomerService;
use Application\Services\Dashboard\HealthScoreInsightService;
use Application\Services\Report\ReportService;
use Illuminate\Container\Container as IlluminateContainer;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class ReportingAndBillingSupportDependencyResolutionTest extends TestCase
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

    public function testReportingAndBillingSupportServicesResolveDependenciesFromContainerWhenAvailable(): void
    {
        $reportRepository = Mockery::mock(ReportRepository::class);
        $documentoRepository = Mockery::mock(DocumentoRepository::class);
        $lancamentoRepository = Mockery::mock(LancamentoRepository::class);
        $metaRepository = Mockery::mock(MetaRepository::class);
        $orcamentoRepository = Mockery::mock(OrcamentoRepository::class);

        $container = new IlluminateContainer();
        $container->instance(ReportRepository::class, $reportRepository);
        $container->instance(DocumentoRepository::class, $documentoRepository);
        $container->instance(OrcamentoRepository::class, $orcamentoRepository);
        ApplicationContainer::setInstance($container);

        $reportService = new ReportService();
        $customerService = new CustomerService();
        $healthScoreInsightService = new HealthScoreInsightService($lancamentoRepository, $metaRepository);

        $this->assertSame($reportRepository, $this->readProperty($reportService, 'repository'));
        $this->assertSame($documentoRepository, $this->readProperty($customerService, 'documentoRepo'));
        $this->assertSame($lancamentoRepository, $this->readProperty($healthScoreInsightService, 'lancamentoRepo'));
        $this->assertSame($metaRepository, $this->readProperty($healthScoreInsightService, 'metaRepo'));
        $this->assertSame($orcamentoRepository, $this->readProperty($healthScoreInsightService, 'orcamentoRepo'));
    }

    private function readProperty(object $object, string $property): mixed
    {
        $reflection = new \ReflectionProperty($object, $property);
        $reflection->setAccessible(true);

        return $reflection->getValue($object);
    }
}
