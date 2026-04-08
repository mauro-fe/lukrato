<?php

declare(strict_types=1);

namespace Tests\Unit\UseCases\Lancamentos;

use Application\Container\ApplicationContainer;
use Application\Repositories\CategoriaRepository;
use Application\Repositories\ContaRepository;
use Application\Repositories\LancamentoRepository;
use Application\Repositories\ParcelamentoRepository;
use Application\Services\Conta\TransferenciaService;
use Application\Services\Lancamento\LancamentoDeletionService;
use Application\Services\Lancamento\LancamentoLimitService;
use Application\Services\Lancamento\LancamentoStatusService;
use Application\Services\Metas\MetaProgressService;
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

class LancamentosUseCaseDependencyResolutionTest extends TestCase
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

    public function testLancamentosUseCasesResolveDependenciesFromContainerWhenAvailable(): void
    {
        $limitService = Mockery::mock(LancamentoLimitService::class);
        $lancamentoRepository = Mockery::mock(LancamentoRepository::class);
        $categoriaRepository = Mockery::mock(CategoriaRepository::class);
        $contaRepository = Mockery::mock(ContaRepository::class);
        $metaProgressService = Mockery::mock(MetaProgressService::class);
        $transferenciaService = Mockery::mock(TransferenciaService::class);
        $statusService = Mockery::mock(LancamentoStatusService::class);
        $parcelamentoRepository = Mockery::mock(ParcelamentoRepository::class);
        $deletionService = Mockery::mock(LancamentoDeletionService::class);

        $container = new IlluminateContainer();
        $container->instance(LancamentoLimitService::class, $limitService);
        $container->instance(LancamentoRepository::class, $lancamentoRepository);
        $container->instance(CategoriaRepository::class, $categoriaRepository);
        $container->instance(ContaRepository::class, $contaRepository);
        $container->instance(MetaProgressService::class, $metaProgressService);
        $container->instance(TransferenciaService::class, $transferenciaService);
        $container->instance(LancamentoStatusService::class, $statusService);
        $container->instance(ParcelamentoRepository::class, $parcelamentoRepository);
        $container->instance(LancamentoDeletionService::class, $deletionService);
        ApplicationContainer::setInstance($container);

        $createLancamentoUseCase = new CreateLancamentoUseCase();
        $updateLancamentoUseCase = new UpdateLancamentoUseCase();
        $updateTransferenciaUseCase = new UpdateTransferenciaUseCase();
        $toggleLancamentoPagoUseCase = new ToggleLancamentoPagoUseCase();
        $createTransferenciaUseCase = new CreateTransferenciaUseCase();
        $deleteLancamentoUseCase = new DeleteLancamentoUseCase();
        $bulkDeleteLancamentosUseCase = new BulkDeleteLancamentosUseCase();

        $this->assertSame($limitService, $this->readProperty($createLancamentoUseCase, 'limitService'));
        $this->assertSame($lancamentoRepository, $this->readProperty($createLancamentoUseCase, 'lancamentoRepo'));
        $this->assertSame($categoriaRepository, $this->readProperty($createLancamentoUseCase, 'categoriaRepo'));
        $this->assertSame($contaRepository, $this->readProperty($createLancamentoUseCase, 'contaRepo'));
        $this->assertSame($metaProgressService, $this->readProperty($createLancamentoUseCase, 'metaProgressService'));

        $this->assertSame($lancamentoRepository, $this->readProperty($updateLancamentoUseCase, 'lancamentoRepo'));
        $this->assertSame($categoriaRepository, $this->readProperty($updateLancamentoUseCase, 'categoriaRepo'));
        $this->assertSame($contaRepository, $this->readProperty($updateLancamentoUseCase, 'contaRepo'));
        $this->assertSame($metaProgressService, $this->readProperty($updateLancamentoUseCase, 'metaProgressService'));

        $this->assertSame($lancamentoRepository, $this->readProperty($updateTransferenciaUseCase, 'lancamentoRepo'));
        $this->assertSame($contaRepository, $this->readProperty($updateTransferenciaUseCase, 'contaRepo'));
        $this->assertSame($metaProgressService, $this->readProperty($updateTransferenciaUseCase, 'metaProgressService'));

        $this->assertSame($lancamentoRepository, $this->readProperty($toggleLancamentoPagoUseCase, 'lancamentoRepo'));
        $this->assertSame($statusService, $this->readProperty($toggleLancamentoPagoUseCase, 'statusService'));
        $this->assertSame($parcelamentoRepository, $this->readProperty($toggleLancamentoPagoUseCase, 'parcelamentoRepo'));

        $this->assertSame($transferenciaService, $this->readProperty($createTransferenciaUseCase, 'transferenciaService'));

        $this->assertSame($lancamentoRepository, $this->readProperty($deleteLancamentoUseCase, 'lancamentoRepo'));
        $this->assertSame($deletionService, $this->readProperty($deleteLancamentoUseCase, 'deletionService'));

        $this->assertSame($lancamentoRepository, $this->readProperty($bulkDeleteLancamentosUseCase, 'lancamentoRepo'));
        $this->assertSame($deletionService, $this->readProperty($bulkDeleteLancamentosUseCase, 'deletionService'));
    }

    private function readProperty(object $object, string $property): mixed
    {
        $reflection = new \ReflectionProperty($object, $property);
        $reflection->setAccessible(true);

        return $reflection->getValue($object);
    }
}
