<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use Application\Container\ApplicationContainer;
use Application\Controllers\Admin\BillingController;
use Application\Controllers\Api\AI\TelegramWebhookController;
use Application\Controllers\Api\AI\UserAiController;
use Application\Controllers\Api\AI\WhatsAppWebhookController;
use Application\Controllers\Api\Lancamentos\MarcarPagoController;
use Application\Controllers\Api\Lancamentos\UpdateController;
use Application\Controllers\Api\Perfil\PerfilController;
use Application\Repositories\DocumentoRepository;
use Application\Repositories\LancamentoRepository;
use Application\Services\AI\Telegram\TelegramWebhookWorkflowService;
use Application\Services\AI\UserAiWorkflowService;
use Application\Services\AI\WhatsApp\WhatsAppWebhookWorkflowService;
use Application\Services\Lancamento\LancamentoUpdateService;
use Application\Services\User\PerfilApiWorkflowService;
use Application\UseCases\Lancamentos\ToggleLancamentoPagoUseCase;
use Application\UseCases\Lancamentos\UpdateTransferenciaUseCase;
use Application\UseCases\Perfil\AvatarUseCase;
use Application\UseCases\Perfil\DashboardPreferencesUseCase;
use Application\UseCases\Perfil\DeleteAccountUseCase;
use Illuminate\Container\Container as IlluminateContainer;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class EntryPointDependencyResolutionTest extends TestCase
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

    public function testAiControllersResolveWorkflowServicesFromContainerWhenAvailable(): void
    {
        $userWorkflow = Mockery::mock(UserAiWorkflowService::class);
        $whatsAppWorkflow = Mockery::mock(WhatsAppWebhookWorkflowService::class);
        $telegramWorkflow = Mockery::mock(TelegramWebhookWorkflowService::class);

        $container = new IlluminateContainer();
        $container->instance(UserAiWorkflowService::class, $userWorkflow);
        $container->instance(WhatsAppWebhookWorkflowService::class, $whatsAppWorkflow);
        $container->instance(TelegramWebhookWorkflowService::class, $telegramWorkflow);
        ApplicationContainer::setInstance($container);

        $userController = new UserAiController();
        $whatsAppController = new WhatsAppWebhookController();
        $telegramController = new TelegramWebhookController();

        $this->assertSame($userWorkflow, $this->invokePrivateMethod($userController, 'workflowService'));
        $this->assertSame($whatsAppWorkflow, $this->invokePrivateMethod($whatsAppController, 'workflowService'));
        $this->assertSame($telegramWorkflow, $this->invokePrivateMethod($telegramController, 'workflowService'));
    }

    public function testPerfilAndLancamentoControllersResolveDependenciesFromContainerWhenAvailable(): void
    {
        $perfilWorkflow = Mockery::mock(PerfilApiWorkflowService::class);
        $avatarUseCase = Mockery::mock(AvatarUseCase::class);
        $dashboardUseCase = Mockery::mock(DashboardPreferencesUseCase::class);
        $deleteUseCase = Mockery::mock(DeleteAccountUseCase::class);
        $togglePagoUseCase = Mockery::mock(ToggleLancamentoPagoUseCase::class);
        $lancamentoRepo = Mockery::mock(LancamentoRepository::class);
        $updateService = Mockery::mock(LancamentoUpdateService::class);
        $updateTransferenciaUseCase = Mockery::mock(UpdateTransferenciaUseCase::class);

        $container = new IlluminateContainer();
        $container->instance(PerfilApiWorkflowService::class, $perfilWorkflow);
        $container->instance(AvatarUseCase::class, $avatarUseCase);
        $container->instance(DashboardPreferencesUseCase::class, $dashboardUseCase);
        $container->instance(DeleteAccountUseCase::class, $deleteUseCase);
        $container->instance(ToggleLancamentoPagoUseCase::class, $togglePagoUseCase);
        $container->instance(LancamentoRepository::class, $lancamentoRepo);
        $container->instance(LancamentoUpdateService::class, $updateService);
        $container->instance(UpdateTransferenciaUseCase::class, $updateTransferenciaUseCase);
        ApplicationContainer::setInstance($container);

        $perfilController = new PerfilController();
        $marcarPagoController = new MarcarPagoController();
        $updateController = new UpdateController();

        $this->assertSame($perfilWorkflow, $this->readProperty($perfilController, 'workflowService'));
        $this->assertSame($avatarUseCase, $this->readProperty($perfilController, 'avatarUseCase'));
        $this->assertSame($dashboardUseCase, $this->readProperty($perfilController, 'dashboardPreferencesUseCase'));
        $this->assertSame($deleteUseCase, $this->readProperty($perfilController, 'deleteAccountUseCase'));
        $this->assertSame($togglePagoUseCase, $this->readProperty($marcarPagoController, 'togglePagoUseCase'));
        $this->assertSame($lancamentoRepo, $this->readProperty($updateController, 'lancamentoRepo'));
        $this->assertSame($updateService, $this->readProperty($updateController, 'updateService'));
        $this->assertSame($updateTransferenciaUseCase, $this->readProperty($updateController, 'updateTransferenciaUseCase'));
    }

    public function testBillingControllerResolvesRepositoryFromContainerWhenAvailable(): void
    {
        $documentoRepository = Mockery::mock(DocumentoRepository::class);

        $container = new IlluminateContainer();
        $container->instance(DocumentoRepository::class, $documentoRepository);
        ApplicationContainer::setInstance($container);

        $controller = new BillingController();

        $this->assertSame($documentoRepository, $this->invokePrivateMethod($controller, 'getDocumentoRepo'));
    }

    private function invokePrivateMethod(object $object, string $method): mixed
    {
        return \Closure::bind(function () use ($method) {
            return $this->{$method}();
        }, $object, $object::class)();
    }

    private function readProperty(object $object, string $property): mixed
    {
        $reflection = new \ReflectionProperty($object, $property);
        $reflection->setAccessible(true);

        return $reflection->getValue($object);
    }
}
