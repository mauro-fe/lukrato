<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api\Cartao;

use Application\Container\ApplicationContainer;
use Application\Controllers\Api\Cartao\CartoesController;
use Application\Core\Exceptions\AuthException;
use Application\Models\Usuario;
use Application\Services\Cartao\CartaoApiWorkflowService;
use Application\Services\Cartao\CartaoCreditoService;
use Application\Services\Cartao\CartaoFaturaService;
use Application\Services\Demo\DemoPreviewService;
use Application\Services\Plan\PlanLimitService;
use Illuminate\Container\Container as IlluminateContainer;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tests\Support\SessionIsolation;

class CartoesControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use SessionIsolation;

    protected function setUp(): void
    {
        parent::setUp();
        ApplicationContainer::flush();
        $this->resetSessionState();
        $_GET = [];
        $_POST = [];
        $_REQUEST = [];
    }

    protected function tearDown(): void
    {
        ApplicationContainer::flush();
        $_GET = [];
        $_POST = [];
        $_REQUEST = [];
        $this->resetSessionState();
        parent::tearDown();
    }

    public function testIndexReturnsSuccessResponse(): void
    {
        $this->seedAuthenticatedSession(1201, 'Cartao User');

        $service = Mockery::mock(CartaoCreditoService::class);
        $service
            ->shouldReceive('listarCartoes')
            ->once()
            ->with(1201, null, true)
            ->andReturn([
                ['id' => 1, 'nome' => 'Cartão Principal'],
            ]);

        $controller = new CartoesController(
            $service,
            Mockery::mock(CartaoFaturaService::class),
            Mockery::mock(PlanLimitService::class),
        );

        $response = $controller->index();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'success' => true,
            'message' => 'Success',
            'data' => [
                ['id' => 1, 'nome' => 'Cartão Principal'],
            ],
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testShowReturnsNotFoundWhenCardDoesNotExist(): void
    {
        $this->seedAuthenticatedSession(1202, 'Cartao User');

        $service = Mockery::mock(CartaoCreditoService::class);
        $service
            ->shouldReceive('buscarCartao')
            ->once()
            ->with(99, 1202)
            ->andReturn(null);

        $controller = new CartoesController(
            $service,
            Mockery::mock(CartaoFaturaService::class),
            Mockery::mock(PlanLimitService::class),
        );

        $response = $controller->show(99);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'Cartão não encontrado',
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testStoreReturnsForbiddenWhenPlanLimitIsReached(): void
    {
        $this->seedAuthenticatedSession(1203, 'Cartao User');

        $planLimitService = Mockery::mock(PlanLimitService::class);
        $planLimitService
            ->shouldReceive('canCreateCartao')
            ->once()
            ->with(1203)
            ->andReturn([
                'allowed' => false,
                'message' => 'Limite de cartões atingido',
                'upgrade_url' => '/upgrade',
                'limit' => 3,
                'used' => 3,
                'remaining' => 0,
            ]);

        $controller = new CartoesController(
            Mockery::mock(CartaoCreditoService::class),
            Mockery::mock(CartaoFaturaService::class),
            $planLimitService,
        );

        $response = $controller->store();

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'Limite de cartões atingido',
            'errors' => [
                'limit_reached' => true,
                'upgrade_url' => '/upgrade',
                'limit_info' => [
                    'limit' => 3,
                    'used' => 3,
                    'remaining' => 0,
                ],
            ],
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testIndexThrowsAuthExceptionWhenSessionIsMissing(): void
    {
        $controller = new CartoesController(
            Mockery::mock(CartaoCreditoService::class),
            Mockery::mock(CartaoFaturaService::class),
            Mockery::mock(PlanLimitService::class),
        );

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Não autenticado');

        $controller->index();
    }

    public function testDeleteReturnsConfirmationPayloadWhenWorkflowRequiresConfirmation(): void
    {
        $this->seedAuthenticatedSession(1204, 'Cartao User');

        $workflow = Mockery::mock(CartaoApiWorkflowService::class);
        $workflow
            ->shouldReceive('deleteCard')
            ->once()
            ->with(77, 1204, [])
            ->andReturn([
                'success' => false,
                'message' => 'Confirme a exclusao',
                'requires_confirmation' => true,
                'total_lancamentos' => 5,
            ]);

        $controller = new CartoesController(
            Mockery::mock(CartaoCreditoService::class),
            Mockery::mock(CartaoFaturaService::class),
            Mockery::mock(PlanLimitService::class),
            $workflow
        );

        $response = $controller->delete(77);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'Confirme a exclusao',
            'errors' => [
                'status' => 'confirm_delete',
                'requires_confirmation' => true,
                'total_lancamentos' => 5,
            ],
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testUpdateLimitReturnsNotFoundWhenWorkflowFails(): void
    {
        $this->seedAuthenticatedSession(1205, 'Cartao User');

        $workflow = Mockery::mock(CartaoApiWorkflowService::class);
        $workflow
            ->shouldReceive('refreshLimit')
            ->once()
            ->with(88, 1205)
            ->andReturn([
                'success' => false,
                'message' => 'Cartao nao encontrado',
            ]);

        $controller = new CartoesController(
            Mockery::mock(CartaoCreditoService::class),
            Mockery::mock(CartaoFaturaService::class),
            Mockery::mock(PlanLimitService::class),
            $workflow
        );

        $response = $controller->updateLimit(88);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'Cartao nao encontrado',
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testCancelarRecorrenciaReturnsBadRequestWhenWorkflowFails(): void
    {
        $this->seedAuthenticatedSession(1206, 'Cartao User');

        $workflow = Mockery::mock(CartaoApiWorkflowService::class);
        $workflow
            ->shouldReceive('cancelRecurring')
            ->once()
            ->with(33, 1206)
            ->andReturn([
                'success' => false,
                'message' => 'Erro ao cancelar recorrencia',
            ]);

        $controller = new CartoesController(
            Mockery::mock(CartaoCreditoService::class),
            Mockery::mock(CartaoFaturaService::class),
            Mockery::mock(PlanLimitService::class),
            $workflow
        );

        $response = $controller->cancelarRecorrencia(33);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'Erro ao cancelar recorrencia',
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testDeactivateReturnsNotFoundWhenWorkflowFails(): void
    {
        $this->seedAuthenticatedSession(1207, 'Cartao User');

        $workflow = Mockery::mock(CartaoApiWorkflowService::class);
        $workflow
            ->shouldReceive('deactivateCard')
            ->once()
            ->with(45, 1207)
            ->andReturn([
                'success' => false,
                'message' => 'Cartao nao encontrado',
            ]);

        $controller = new CartoesController(
            Mockery::mock(CartaoCreditoService::class),
            Mockery::mock(CartaoFaturaService::class),
            Mockery::mock(PlanLimitService::class),
            $workflow
        );

        $response = $controller->deactivate(45);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'Cartao nao encontrado',
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testConstructorResolvesContainerManagedDependenciesWhenAvailable(): void
    {
        $workflow = Mockery::mock(CartaoApiWorkflowService::class);
        $demoPreviewService = Mockery::mock(DemoPreviewService::class);

        $container = new IlluminateContainer();
        $container->instance(CartaoApiWorkflowService::class, $workflow);
        $container->instance(DemoPreviewService::class, $demoPreviewService);
        ApplicationContainer::setInstance($container);

        $controller = new CartoesController();

        $this->assertSame($workflow, $this->readProperty($controller, 'workflowService'));
        $this->assertSame($demoPreviewService, $this->readProperty($controller, 'demoPreviewService'));
    }

    private function seedAuthenticatedSession(int $userId, string $name): void
    {
        $this->startIsolatedSession('cartoes-controller-test');

        $user = new Usuario();
        $user->id = $userId;
        $user->nome = $name;
        $user->is_admin = 0;
        $user->senha = password_hash('Senha@123', PASSWORD_DEFAULT);

        $_SESSION['usuario_logged_in'] = true;
        $_SESSION['user_id'] = $userId;
        $_SESSION['usuario_nome'] = $name;
        $_SESSION['usuario_cache'] = [
            'id' => $userId,
            'data' => $user,
        ];
    }

    private function readProperty(object $object, string $property): mixed
    {
        $reflection = new \ReflectionProperty($object, $property);
        $reflection->setAccessible(true);

        return $reflection->getValue($object);
    }
}
