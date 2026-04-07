<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api\Metas;

use Application\Container\ApplicationContainer;
use Application\Controllers\Api\Metas\MetasController;
use Application\Models\Usuario;
use Application\Services\Metas\MetaService;
use Application\UseCases\Metas\AddMetaAporteUseCase;
use Application\UseCases\Metas\CreateMetaUseCase;
use Application\UseCases\Metas\DeleteMetaUseCase;
use Application\UseCases\Metas\GetMetaTemplatesUseCase;
use Application\UseCases\Metas\GetMetasListUseCase;
use Application\UseCases\Metas\UpdateMetaUseCase;
use Illuminate\Container\Container as IlluminateContainer;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tests\Support\SessionIsolation;

class MetasControllerTest extends TestCase
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
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    protected function tearDown(): void
    {
        ApplicationContainer::flush();
        unset($_SERVER['REQUEST_METHOD']);
        $_GET = [];
        $_POST = [];
        $this->resetSessionState();

        parent::tearDown();
    }

    public function testStoreReturnsValidationResponseWhenPayloadIsInvalid(): void
    {
        $this->seedAuthenticatedUserSession(11, 'Metas User');
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $controller = $this->buildController();

        $response = $controller->store();
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertFalse($payload['success']);
        $this->assertSame('Validation failed', $payload['message']);
        $this->assertArrayHasKey('titulo', $payload['errors']);
        $this->assertArrayHasKey('valor_alvo', $payload['errors']);
    }

    public function testUpdateReturnsNotFoundResponseWhenServiceDoesNotFindMeta(): void
    {
        $this->seedAuthenticatedUserSession(12, 'Metas Update');
        $_SERVER['REQUEST_METHOD'] = 'PUT';

        $metaService = Mockery::mock(MetaService::class);
        $metaService
            ->shouldReceive('atualizar')
            ->once()
            ->with(12, 99, [])
            ->andReturnNull();

        $controller = $this->buildController(metaService: $metaService);

        $response = $controller->update(99);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'Meta não encontrada.',
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testConstructorResolvesUseCasesFromContainerWhenAvailable(): void
    {
        $createMetaUseCase = Mockery::mock(CreateMetaUseCase::class);
        $updateMetaUseCase = Mockery::mock(UpdateMetaUseCase::class);
        $addMetaAporteUseCase = Mockery::mock(AddMetaAporteUseCase::class);
        $deleteMetaUseCase = Mockery::mock(DeleteMetaUseCase::class);
        $getMetaTemplatesUseCase = Mockery::mock(GetMetaTemplatesUseCase::class);
        $getMetasListUseCase = Mockery::mock(GetMetasListUseCase::class);

        $container = new IlluminateContainer();
        $container->instance(CreateMetaUseCase::class, $createMetaUseCase);
        $container->instance(UpdateMetaUseCase::class, $updateMetaUseCase);
        $container->instance(AddMetaAporteUseCase::class, $addMetaAporteUseCase);
        $container->instance(DeleteMetaUseCase::class, $deleteMetaUseCase);
        $container->instance(GetMetaTemplatesUseCase::class, $getMetaTemplatesUseCase);
        $container->instance(GetMetasListUseCase::class, $getMetasListUseCase);
        ApplicationContainer::setInstance($container);

        $controller = new MetasController();

        $this->assertSame($createMetaUseCase, $this->readProperty($controller, 'createMetaUseCase'));
        $this->assertSame($updateMetaUseCase, $this->readProperty($controller, 'updateMetaUseCase'));
        $this->assertSame($addMetaAporteUseCase, $this->readProperty($controller, 'addMetaAporteUseCase'));
        $this->assertSame($deleteMetaUseCase, $this->readProperty($controller, 'deleteMetaUseCase'));
        $this->assertSame($getMetaTemplatesUseCase, $this->readProperty($controller, 'getMetaTemplatesUseCase'));
        $this->assertSame($getMetasListUseCase, $this->readProperty($controller, 'getMetasListUseCase'));
    }

    private function buildController(?MetaService $metaService = null): MetasController
    {
        return new MetasController(
            $metaService ?? Mockery::mock(MetaService::class),
        );
    }

    private function seedAuthenticatedUserSession(int $userId, string $name): void
    {
        $this->startIsolatedSession('metas-controller-test');

        $user = new Usuario();
        $user->id = $userId;
        $user->nome = $name;
        $user->is_admin = 0;

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
