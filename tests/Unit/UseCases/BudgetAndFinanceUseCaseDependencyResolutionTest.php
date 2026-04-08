<?php

declare(strict_types=1);

namespace Tests\Unit\UseCases;

use Application\Container\ApplicationContainer;
use Application\Repositories\CategoriaRepository;
use Application\Repositories\ContaRepository;
use Application\Repositories\LancamentoRepository;
use Application\Services\Demo\DemoPreviewService;
use Application\Services\Gamification\AchievementService;
use Application\Services\Metas\MetaService;
use Application\Services\Orcamentos\OrcamentoService;
use Application\UseCases\Financas\GetFinanceiroMetricsUseCase;
use Application\UseCases\Financas\GetFinanceiroOptionsUseCase;
use Application\UseCases\Financas\GetFinanceiroTransactionsUseCase;
use Application\UseCases\Financas\GetFinancasInsightsUseCase;
use Application\UseCases\Financas\GetFinancasResumoUseCase;
use Application\UseCases\Metas\AddMetaAporteUseCase;
use Application\UseCases\Metas\CreateMetaUseCase;
use Application\UseCases\Metas\DeleteMetaUseCase;
use Application\UseCases\Metas\GetMetaTemplatesUseCase;
use Application\UseCases\Metas\GetMetasListUseCase;
use Application\UseCases\Metas\UpdateMetaUseCase;
use Application\UseCases\Orcamentos\ApplyOrcamentoSugestoesUseCase;
use Application\UseCases\Orcamentos\BulkSaveOrcamentosUseCase;
use Application\UseCases\Orcamentos\CopyOrcamentosMesUseCase;
use Application\UseCases\Orcamentos\DeleteOrcamentoUseCase;
use Application\UseCases\Orcamentos\GetOrcamentoSugestoesUseCase;
use Application\UseCases\Orcamentos\GetOrcamentosListUseCase;
use Application\UseCases\Orcamentos\SaveOrcamentoUseCase;
use Illuminate\Container\Container as IlluminateContainer;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class BudgetAndFinanceUseCaseDependencyResolutionTest extends TestCase
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

    public function testBudgetAndFinanceUseCasesResolveDependenciesFromContainerWhenAvailable(): void
    {
        $orcamentoService = Mockery::mock(OrcamentoService::class);
        $metaService = Mockery::mock(MetaService::class);
        $demoPreviewService = Mockery::mock(DemoPreviewService::class);
        $achievementService = Mockery::mock(AchievementService::class);
        $lancamentoRepository = Mockery::mock(LancamentoRepository::class);
        $categoriaRepository = Mockery::mock(CategoriaRepository::class);
        $contaRepository = Mockery::mock(ContaRepository::class);

        $container = new IlluminateContainer();
        $container->instance(OrcamentoService::class, $orcamentoService);
        $container->instance(MetaService::class, $metaService);
        $container->instance(DemoPreviewService::class, $demoPreviewService);
        $container->instance(AchievementService::class, $achievementService);
        $container->instance(LancamentoRepository::class, $lancamentoRepository);
        $container->instance(CategoriaRepository::class, $categoriaRepository);
        $container->instance(ContaRepository::class, $contaRepository);
        ApplicationContainer::setInstance($container);

        $saveOrcamentoUseCase = new SaveOrcamentoUseCase();
        $getOrcamentoSugestoesUseCase = new GetOrcamentoSugestoesUseCase();
        $getOrcamentosListUseCase = new GetOrcamentosListUseCase();
        $deleteOrcamentoUseCase = new DeleteOrcamentoUseCase();
        $copyOrcamentosMesUseCase = new CopyOrcamentosMesUseCase();
        $bulkSaveOrcamentosUseCase = new BulkSaveOrcamentosUseCase();
        $applyOrcamentoSugestoesUseCase = new ApplyOrcamentoSugestoesUseCase();
        $updateMetaUseCase = new UpdateMetaUseCase();
        $getMetaTemplatesUseCase = new GetMetaTemplatesUseCase();
        $getMetasListUseCase = new GetMetasListUseCase();
        $deleteMetaUseCase = new DeleteMetaUseCase();
        $createMetaUseCase = new CreateMetaUseCase();
        $addMetaAporteUseCase = new AddMetaAporteUseCase();
        $getFinanceiroTransactionsUseCase = new GetFinanceiroTransactionsUseCase();
        $getFinanceiroOptionsUseCase = new GetFinanceiroOptionsUseCase();
        $getFinanceiroMetricsUseCase = new GetFinanceiroMetricsUseCase();
        $getFinancasResumoUseCase = new GetFinancasResumoUseCase();
        $getFinancasInsightsUseCase = new GetFinancasInsightsUseCase();

        $this->assertSame($orcamentoService, $this->readProperty($saveOrcamentoUseCase, 'orcamentoService'));
        $this->assertSame($orcamentoService, $this->readProperty($getOrcamentoSugestoesUseCase, 'orcamentoService'));
        $this->assertSame($orcamentoService, $this->readProperty($getOrcamentosListUseCase, 'orcamentoService'));
        $this->assertSame($demoPreviewService, $this->readProperty($getOrcamentosListUseCase, 'demoPreviewService'));
        $this->assertSame($orcamentoService, $this->readProperty($deleteOrcamentoUseCase, 'orcamentoService'));
        $this->assertSame($orcamentoService, $this->readProperty($copyOrcamentosMesUseCase, 'orcamentoService'));
        $this->assertSame($orcamentoService, $this->readProperty($bulkSaveOrcamentosUseCase, 'orcamentoService'));
        $this->assertSame($orcamentoService, $this->readProperty($applyOrcamentoSugestoesUseCase, 'orcamentoService'));

        $this->assertSame($metaService, $this->readProperty($updateMetaUseCase, 'metaService'));
        $this->assertSame($metaService, $this->readProperty($getMetaTemplatesUseCase, 'metaService'));
        $this->assertSame($metaService, $this->readProperty($getMetasListUseCase, 'metaService'));
        $this->assertSame($demoPreviewService, $this->readProperty($getMetasListUseCase, 'demoPreviewService'));
        $this->assertSame($metaService, $this->readProperty($deleteMetaUseCase, 'metaService'));
        $this->assertSame($metaService, $this->readProperty($createMetaUseCase, 'metaService'));
        $this->assertSame($achievementService, $this->readProperty($createMetaUseCase, 'achievementService'));
        $this->assertSame($metaService, $this->readProperty($addMetaAporteUseCase, 'metaService'));
        $this->assertSame($achievementService, $this->readProperty($addMetaAporteUseCase, 'achievementService'));

        $this->assertSame($lancamentoRepository, $this->readProperty($getFinanceiroTransactionsUseCase, 'lancamentoRepo'));
        $this->assertSame($categoriaRepository, $this->readProperty($getFinanceiroOptionsUseCase, 'categoriaRepo'));
        $this->assertSame($contaRepository, $this->readProperty($getFinanceiroOptionsUseCase, 'contaRepo'));
        $this->assertSame($lancamentoRepository, $this->readProperty($getFinanceiroMetricsUseCase, 'lancamentoRepo'));
        $this->assertSame($metaService, $this->readProperty($getFinancasResumoUseCase, 'metaService'));
        $this->assertSame($orcamentoService, $this->readProperty($getFinancasResumoUseCase, 'orcamentoService'));
        $this->assertSame($demoPreviewService, $this->readProperty($getFinancasResumoUseCase, 'demoPreviewService'));
        $this->assertSame($orcamentoService, $this->readProperty($getFinancasInsightsUseCase, 'orcamentoService'));
        $this->assertSame($demoPreviewService, $this->readProperty($getFinancasInsightsUseCase, 'demoPreviewService'));
    }

    private function readProperty(object $object, string $property): mixed
    {
        $reflection = new \ReflectionProperty($object, $property);
        $reflection->setAccessible(true);

        return $reflection->getValue($object);
    }
}
