<?php

declare(strict_types=1);

namespace Tests\Unit\Services\AI;

use Application\Container\ApplicationContainer;
use Application\Repositories\CategoriaRepository;
use Application\Repositories\ContaRepository;
use Application\Services\AI\Actions\CreateCategoriaAction;
use Application\Services\AI\Actions\CreateContaAction;
use Application\Services\AI\Actions\CreateLancamentoAction;
use Application\Services\AI\Actions\CreateMetaAction;
use Application\Services\AI\Actions\CreateOrcamentoAction;
use Application\Services\AI\Actions\CreateSubcategoriaAction;
use Application\Services\AI\Actions\PayFaturaAction;
use Application\Services\AI\Actions\ActionRegistry;
use Application\Services\Cartao\CartaoFaturaService;
use Application\Services\Categoria\SubcategoriaService;
use Application\Services\Conta\ContaService;
use Application\Services\Gamification\AchievementService;
use Application\Services\AI\Handlers\ConfirmationHandler;
use Application\Services\AI\Handlers\EntityCreationHandler;
use Application\Services\AI\Handlers\TransactionExtractorHandler;
use Application\Services\Lancamento\LancamentoCreationService;
use Application\Services\Metas\MetaService;
use Application\Services\Orcamentos\OrcamentoService;
use Illuminate\Container\Container as IlluminateContainer;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class AiActionHandlingDependencyResolutionTest extends TestCase
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

    public function testHandlersResolveDependenciesFromContainerWhenAvailable(): void
    {
        $actionRegistry = Mockery::mock(ActionRegistry::class);
        $contaRepository = Mockery::mock(ContaRepository::class);

        $container = new IlluminateContainer();
        $container->instance(ActionRegistry::class, $actionRegistry);
        $container->instance(ContaRepository::class, $contaRepository);
        ApplicationContainer::setInstance($container);

        $confirmationHandler = new ConfirmationHandler();
        $entityCreationHandler = new EntityCreationHandler();
        $transactionExtractorHandler = new TransactionExtractorHandler();

        $this->assertSame($actionRegistry, $this->readProperty($confirmationHandler, 'actionRegistry'));
        $this->assertSame($contaRepository, $this->readProperty($confirmationHandler, 'contaRepository'));
        $this->assertSame($contaRepository, $this->readProperty($entityCreationHandler, 'contaRepository'));
        $this->assertSame($contaRepository, $this->readProperty($transactionExtractorHandler, 'contaRepository'));
    }

    public function testActionRegistryAndActionsResolveDependenciesFromContainerWhenAvailable(): void
    {
        $createLancamentoAction = Mockery::mock(CreateLancamentoAction::class);
        $createMetaAction = Mockery::mock(CreateMetaAction::class);
        $createOrcamentoAction = Mockery::mock(CreateOrcamentoAction::class);
        $createCategoriaAction = Mockery::mock(CreateCategoriaAction::class);
        $createSubcategoriaAction = Mockery::mock(CreateSubcategoriaAction::class);
        $createContaAction = Mockery::mock(CreateContaAction::class);
        $payFaturaAction = Mockery::mock(PayFaturaAction::class);
        $lancamentoCreationService = Mockery::mock(LancamentoCreationService::class);
        $metaService = Mockery::mock(MetaService::class);
        $orcamentoService = Mockery::mock(OrcamentoService::class);
        $contaService = Mockery::mock(ContaService::class);
        $categoriaRepository = Mockery::mock(CategoriaRepository::class);
        $subcategoriaService = Mockery::mock(SubcategoriaService::class);
        $cartaoFaturaService = Mockery::mock(CartaoFaturaService::class);
        $achievementService = Mockery::mock(AchievementService::class);

        $container = new IlluminateContainer();
        $container->instance(CreateLancamentoAction::class, $createLancamentoAction);
        $container->instance(CreateMetaAction::class, $createMetaAction);
        $container->instance(CreateOrcamentoAction::class, $createOrcamentoAction);
        $container->instance(CreateCategoriaAction::class, $createCategoriaAction);
        $container->instance(CreateSubcategoriaAction::class, $createSubcategoriaAction);
        $container->instance(CreateContaAction::class, $createContaAction);
        $container->instance(PayFaturaAction::class, $payFaturaAction);
        $container->instance(LancamentoCreationService::class, $lancamentoCreationService);
        $container->instance(MetaService::class, $metaService);
        $container->instance(OrcamentoService::class, $orcamentoService);
        $container->instance(ContaService::class, $contaService);
        $container->instance(CategoriaRepository::class, $categoriaRepository);
        $container->instance(SubcategoriaService::class, $subcategoriaService);
        $container->instance(CartaoFaturaService::class, $cartaoFaturaService);
        $container->instance(AchievementService::class, $achievementService);
        ApplicationContainer::setInstance($container);

        $registry = new ActionRegistry();
        $resolvedCreateLancamentoAction = new CreateLancamentoAction();
        $resolvedCreateMetaAction = new CreateMetaAction();
        $resolvedCreateOrcamentoAction = new CreateOrcamentoAction();
        $resolvedCreateContaAction = new CreateContaAction();
        $resolvedCreateCategoriaAction = new CreateCategoriaAction();
        $resolvedCreateSubcategoriaAction = new CreateSubcategoriaAction();
        $resolvedPayFaturaAction = new PayFaturaAction();

        $actions = $this->readProperty($registry, 'actions');

        $this->assertSame($createLancamentoAction, $actions['create_lancamento']);
        $this->assertSame($createMetaAction, $actions['create_meta']);
        $this->assertSame($createOrcamentoAction, $actions['create_orcamento']);
        $this->assertSame($createCategoriaAction, $actions['create_categoria']);
        $this->assertSame($createSubcategoriaAction, $actions['create_subcategoria']);
        $this->assertSame($createContaAction, $actions['create_conta']);
        $this->assertSame($payFaturaAction, $actions['pay_fatura']);

        $this->assertSame($lancamentoCreationService, $this->readProperty($resolvedCreateLancamentoAction, 'service'));
        $this->assertSame($metaService, $this->readProperty($resolvedCreateMetaAction, 'service'));
        $this->assertSame($orcamentoService, $this->readProperty($resolvedCreateOrcamentoAction, 'service'));
        $this->assertSame($contaService, $this->readProperty($resolvedCreateContaAction, 'service'));
        $this->assertSame($categoriaRepository, $this->readProperty($resolvedCreateCategoriaAction, 'repo'));
        $this->assertSame($subcategoriaService, $this->readProperty($resolvedCreateSubcategoriaAction, 'service'));
        $this->assertSame($cartaoFaturaService, $this->readProperty($resolvedPayFaturaAction, 'service'));
        $this->assertSame($achievementService, $this->readProperty($resolvedPayFaturaAction, 'achievementService'));
    }

    private function readProperty(object $object, string $property): mixed
    {
        $reflection = new \ReflectionProperty($object, $property);
        $reflection->setAccessible(true);

        return $reflection->getValue($object);
    }
}
