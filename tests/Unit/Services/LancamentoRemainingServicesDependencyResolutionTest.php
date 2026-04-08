<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Application\Container\ApplicationContainer;
use Application\Repositories\LancamentoRepository;
use Application\Services\Cartao\CartaoCreditoLancamentoService;
use Application\Services\Gamification\AchievementService;
use Application\Services\Gamification\GamificationService;
use Application\Services\Lancamento\LancamentoCreationService;
use Application\Services\Lancamento\LancamentoExportService;
use Application\Services\Lancamento\LancamentoLimitService;
use Application\Services\Lancamento\LancamentoRecurrenceService;
use Application\Services\Lancamento\LancamentoStatusService;
use Application\Services\Lancamento\LancamentoUpdateService;
use Application\Services\Metas\MetaProgressService;
use Application\Services\Plan\UserPlanService;
use Application\Services\Report\ExcelExportService;
use Application\Services\Report\PdfExportService;
use Illuminate\Container\Container as IlluminateContainer;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class LancamentoRemainingServicesDependencyResolutionTest extends TestCase
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

    public function testRemainingLancamentoServicesResolveDependenciesFromContainerWhenAvailable(): void
    {
        $cartaoService = Mockery::mock(CartaoCreditoLancamentoService::class);
        $lancamentoRepository = Mockery::mock(LancamentoRepository::class);
        $gamificationService = Mockery::mock(GamificationService::class);
        $limitService = Mockery::mock(LancamentoLimitService::class);
        $planService = Mockery::mock(UserPlanService::class);
        $metaProgressService = Mockery::mock(MetaProgressService::class);
        $recurrenceService = Mockery::mock(LancamentoRecurrenceService::class);
        $achievementService = Mockery::mock(AchievementService::class);
        $statusService = Mockery::mock(LancamentoStatusService::class);
        $excelExporter = Mockery::mock(ExcelExportService::class);
        $pdfExporter = Mockery::mock(PdfExportService::class);

        $container = new IlluminateContainer();
        $container->instance(CartaoCreditoLancamentoService::class, $cartaoService);
        $container->instance(LancamentoRepository::class, $lancamentoRepository);
        $container->instance(GamificationService::class, $gamificationService);
        $container->instance(LancamentoLimitService::class, $limitService);
        $container->instance(UserPlanService::class, $planService);
        $container->instance(MetaProgressService::class, $metaProgressService);
        $container->instance(LancamentoRecurrenceService::class, $recurrenceService);
        $container->instance(AchievementService::class, $achievementService);
        $container->instance(LancamentoStatusService::class, $statusService);
        $container->instance(ExcelExportService::class, $excelExporter);
        $container->instance(PdfExportService::class, $pdfExporter);
        ApplicationContainer::setInstance($container);

        $recurrenceServiceOnly = new LancamentoRecurrenceService();
        $creationService = new LancamentoCreationService();
        $updateService = new LancamentoUpdateService();
        $exportService = new LancamentoExportService();

        $this->assertSame($lancamentoRepository, $this->readProperty($recurrenceServiceOnly, 'lancamentoRepo'));
        $this->assertSame($metaProgressService, $this->readProperty($recurrenceServiceOnly, 'metaProgressService'));

        $this->assertSame($cartaoService, $this->readProperty($creationService, 'cartaoService'));
        $this->assertSame($lancamentoRepository, $this->readProperty($creationService, 'lancamentoRepo'));
        $this->assertSame($gamificationService, $this->readProperty($creationService, 'gamificationService'));
        $this->assertSame($limitService, $this->readProperty($creationService, 'limitService'));
        $this->assertSame($planService, $this->readProperty($creationService, 'planService'));
        $this->assertSame($metaProgressService, $this->readProperty($creationService, 'metaProgressService'));
        $this->assertSame($recurrenceService, $this->readProperty($creationService, 'recurrenceService'));
        $this->assertSame($achievementService, $this->readProperty($creationService, 'achievementService'));

        $this->assertSame($lancamentoRepository, $this->readProperty($updateService, 'lancamentoRepo'));
        $this->assertSame($statusService, $this->readProperty($updateService, 'statusService'));
        $this->assertSame($metaProgressService, $this->readProperty($updateService, 'metaProgressService'));

        $this->assertSame($excelExporter, $this->readProperty($exportService, 'excelExporter'));
        $this->assertSame($pdfExporter, $this->readProperty($exportService, 'pdfExporter'));
    }

    private function readProperty(object $object, string $property): mixed
    {
        $reflection = new \ReflectionProperty($object, $property);
        $reflection->setAccessible(true);

        return $reflection->getValue($object);
    }
}
