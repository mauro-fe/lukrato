<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api\Lancamentos;

use Application\Container\ApplicationContainer;
use Application\Controllers\Api\Fatura\ParcelamentosController;
use Application\Controllers\Api\Lancamentos\CancelarRecorrenciaController;
use Application\Controllers\Api\Lancamentos\DestroyController;
use Application\Controllers\Api\Lancamentos\ExportController;
use Application\Controllers\Api\Lancamentos\FaturaDetalhesController;
use Application\Controllers\Api\Lancamentos\IndexController;
use Application\Controllers\Api\Lancamentos\MarcarPagoController;
use Application\Controllers\Api\Lancamentos\StoreController;
use Application\Controllers\Api\Lancamentos\TransactionsController;
use Application\Controllers\Api\Lancamentos\UpdateController;
use Application\Controllers\Api\Lancamentos\UsageController;
use Application\Repositories\CategoriaRepository;
use Application\Repositories\ContaRepository;
use Application\Repositories\FaturaCartaoRepository;
use Application\Repositories\LancamentoRepository;
use Application\Repositories\ParcelamentoRepository;
use Application\Services\Lancamento\LancamentoCreationService;
use Application\Services\Lancamento\LancamentoExportService;
use Application\Services\Lancamento\LancamentoLimitService;
use Application\Services\Lancamento\LancamentoUpdateService;
use Application\UseCases\Lancamentos\BulkDeleteLancamentosUseCase;
use Application\UseCases\Lancamentos\CreateLancamentoUseCase;
use Application\UseCases\Lancamentos\CreateTransferenciaUseCase;
use Application\UseCases\Lancamentos\DeleteLancamentoUseCase;
use Application\UseCases\Lancamentos\ToggleLancamentoPagoUseCase;
use Application\UseCases\Lancamentos\UpdateLancamentoUseCase;
use Application\UseCases\Lancamentos\UpdateTransferenciaUseCase;
use Illuminate\Container\Container as IlluminateContainer;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class LancamentosDependencyResolutionTest extends TestCase
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

    public function testLancamentosControllersResolveDependenciesFromContainerWhenAvailable(): void
    {
        $limitService = Mockery::mock(LancamentoLimitService::class);
        $creationService = Mockery::mock(LancamentoCreationService::class);
        $lancamentoRepository = Mockery::mock(LancamentoRepository::class);
        $faturaCartaoRepository = Mockery::mock(FaturaCartaoRepository::class);
        $exportService = Mockery::mock(LancamentoExportService::class);
        $bulkDeleteUseCase = Mockery::mock(BulkDeleteLancamentosUseCase::class);
        $deleteUseCase = Mockery::mock(DeleteLancamentoUseCase::class);
        $createLancamentoUseCase = Mockery::mock(CreateLancamentoUseCase::class);
        $updateLancamentoUseCase = Mockery::mock(UpdateLancamentoUseCase::class);
        $createTransferenciaUseCase = Mockery::mock(CreateTransferenciaUseCase::class);
        $togglePagoUseCase = Mockery::mock(ToggleLancamentoPagoUseCase::class);
        $updateService = Mockery::mock(LancamentoUpdateService::class);
        $updateTransferenciaUseCase = Mockery::mock(UpdateTransferenciaUseCase::class);
        $parcelamentoRepository = Mockery::mock(ParcelamentoRepository::class);
        $categoriaRepository = Mockery::mock(CategoriaRepository::class);
        $contaRepository = Mockery::mock(ContaRepository::class);

        $container = new IlluminateContainer();
        $container->instance(LancamentoLimitService::class, $limitService);
        $container->instance(LancamentoCreationService::class, $creationService);
        $container->instance(LancamentoRepository::class, $lancamentoRepository);
        $container->instance(FaturaCartaoRepository::class, $faturaCartaoRepository);
        $container->instance(LancamentoExportService::class, $exportService);
        $container->instance(BulkDeleteLancamentosUseCase::class, $bulkDeleteUseCase);
        $container->instance(DeleteLancamentoUseCase::class, $deleteUseCase);
        $container->instance(CreateLancamentoUseCase::class, $createLancamentoUseCase);
        $container->instance(UpdateLancamentoUseCase::class, $updateLancamentoUseCase);
        $container->instance(CreateTransferenciaUseCase::class, $createTransferenciaUseCase);
        $container->instance(ToggleLancamentoPagoUseCase::class, $togglePagoUseCase);
        $container->instance(LancamentoUpdateService::class, $updateService);
        $container->instance(UpdateTransferenciaUseCase::class, $updateTransferenciaUseCase);
        $container->instance(ParcelamentoRepository::class, $parcelamentoRepository);
        $container->instance(CategoriaRepository::class, $categoriaRepository);
        $container->instance(ContaRepository::class, $contaRepository);
        ApplicationContainer::setInstance($container);

        $usageController = new UsageController();
        $cancelarRecorrenciaController = new CancelarRecorrenciaController();
        $storeController = new StoreController();
        $indexController = new IndexController();
        $faturaDetalhesController = new FaturaDetalhesController();
        $exportController = new ExportController();
        $destroyController = new DestroyController();
        $marcarPagoController = new MarcarPagoController();
        $transactionsController = new TransactionsController();
        $updateController = new UpdateController();
        $parcelamentosController = new ParcelamentosController();

        $this->assertSame($limitService, $this->readProperty($usageController, 'limitService'));
        $this->assertSame($creationService, $this->readProperty($cancelarRecorrenciaController, 'creationService'));
        $this->assertSame($creationService, $this->readProperty($storeController, 'creationService'));
        $this->assertSame($lancamentoRepository, $this->readProperty($indexController, 'lancamentoRepo'));
        $this->assertSame($lancamentoRepository, $this->readProperty($faturaDetalhesController, 'lancamentoRepo'));
        $this->assertSame($faturaCartaoRepository, $this->readProperty($faturaDetalhesController, 'faturaCartaoRepo'));
        $this->assertSame($exportService, $this->readProperty($exportController, 'exportService'));
        $this->assertSame($bulkDeleteUseCase, $this->readProperty($destroyController, 'bulkDeleteUseCase'));
        $this->assertSame($deleteUseCase, $this->readProperty($destroyController, 'deleteUseCase'));
        $this->assertSame($togglePagoUseCase, $this->readProperty($marcarPagoController, 'togglePagoUseCase'));
        $this->assertSame($createLancamentoUseCase, $this->readProperty($transactionsController, 'createLancamentoUseCase'));
        $this->assertSame($updateLancamentoUseCase, $this->readProperty($transactionsController, 'updateLancamentoUseCase'));
        $this->assertSame($createTransferenciaUseCase, $this->readProperty($transactionsController, 'createTransferenciaUseCase'));
        $this->assertSame($lancamentoRepository, $this->readProperty($updateController, 'lancamentoRepo'));
        $this->assertSame($updateService, $this->readProperty($updateController, 'updateService'));
        $this->assertSame($updateTransferenciaUseCase, $this->readProperty($updateController, 'updateTransferenciaUseCase'));
        $this->assertSame($parcelamentoRepository, $this->readProperty($parcelamentosController, 'parcelamentoRepo'));
        $this->assertSame($categoriaRepository, $this->readProperty($parcelamentosController, 'categoriaRepo'));
        $this->assertSame($contaRepository, $this->readProperty($parcelamentosController, 'contaRepo'));
    }

    private function readProperty(object $object, string $property): mixed
    {
        $reflection = new \ReflectionProperty($object, $property);
        $reflection->setAccessible(true);

        return $reflection->getValue($object);
    }
}
