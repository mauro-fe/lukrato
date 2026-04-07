<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\SysAdmin;

use Application\Container\ApplicationContainer;
use Application\Controllers\SysAdmin\BlogController;
use Application\Core\Exceptions\AuthException;
use Application\Models\Usuario;
use Application\Services\Admin\BlogAdminWorkflowService;
use Illuminate\Container\Container as IlluminateContainer;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tests\Support\SessionIsolation;

class BlogControllerTest extends TestCase
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
        $controller = new BlogController();

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Não autenticado');

        $controller->index();
    }

    public function testConstructorResolvesWorkflowFromContainerWhenAvailable(): void
    {
        $workflowService = Mockery::mock(BlogAdminWorkflowService::class);

        $container = new IlluminateContainer();
        $container->instance(BlogAdminWorkflowService::class, $workflowService);
        ApplicationContainer::setInstance($container);

        $controller = new BlogController();

        $this->assertSame($workflowService, $this->readProperty($controller, 'workflowService'));
    }

    public function testStoreReturnsValidationErrorsWhenPayloadIsEmpty(): void
    {
        $this->seedAuthenticatedSession(2301, 'Blog Admin', true);

        $controller = new BlogController();

        $response = $controller->store();

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => [
                'titulo' => 'O título é obrigatório.',
                'conteudo' => 'O conteúdo é obrigatório.',
            ],
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    private function seedAuthenticatedSession(int $userId, string $name, bool $isAdmin): void
    {
        $this->startIsolatedSession('blog-controller-test');

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
