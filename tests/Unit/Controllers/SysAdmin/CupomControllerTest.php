<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\SysAdmin;

use Application\Container\ApplicationContainer;
use Application\Controllers\SysAdmin\CupomController;
use Application\Core\Exceptions\AuthException;
use Application\Models\Usuario;
use Application\Services\Admin\CupomAdminWorkflowService;
use Illuminate\Container\Container as IlluminateContainer;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tests\Support\SessionIsolation;

class CupomControllerTest extends TestCase
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
        $_SERVER['REQUEST_METHOD'] = 'POST';
    }

    protected function tearDown(): void
    {
        ApplicationContainer::flush();
        $_GET = [];
        $_POST = [];
        $_REQUEST = [];
        unset($_SERVER['REQUEST_METHOD']);
        $this->resetSessionState();
        parent::tearDown();
    }

    public function testIndexThrowsAuthExceptionWhenSessionIsMissing(): void
    {
        $controller = new CupomController();

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Não autenticado');

        $controller->index();
    }

    public function testConstructorResolvesWorkflowFromContainerWhenAvailable(): void
    {
        $workflowService = Mockery::mock(CupomAdminWorkflowService::class);

        $container = new IlluminateContainer();
        $container->instance(CupomAdminWorkflowService::class, $workflowService);
        ApplicationContainer::setInstance($container);

        $controller = new CupomController();

        $this->assertSame($workflowService, $this->readProperty($controller, 'workflowService'));
    }

    public function testStoreReturnsBadRequestWhenCodeIsMissing(): void
    {
        $this->seedAuthenticatedSession(2401, 'Cupom Admin', true);

        $controller = new CupomController();

        $response = $controller->store();

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'Codigo do cupom e obrigatorio',
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testValidarReturnsBadRequestWhenCodeIsMissing(): void
    {
        $this->seedAuthenticatedSession(2402, 'Cupom User', false);
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $controller = new CupomController();

        $response = $controller->validar();

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'Codigo do cupom e obrigatorio',
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    private function seedAuthenticatedSession(int $userId, string $name, bool $isAdmin): void
    {
        $this->startIsolatedSession('cupom-controller-test');

        $user = new Usuario();
        $user->id = $userId;
        $user->nome = $name;
        $user->is_admin = $isAdmin ? 1 : 0;

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
