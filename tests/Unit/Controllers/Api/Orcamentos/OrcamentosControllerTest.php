<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api\Orcamentos;

use Application\Container\ApplicationContainer;
use Application\Controllers\Api\Orcamentos\OrcamentosController;
use Application\Core\Exceptions\AuthException;
use Application\DTO\ServiceResultDTO;
use Application\Models\Usuario;
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
use Tests\Support\SessionIsolation;

class OrcamentosControllerTest extends TestCase
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
        $_SERVER['REQUEST_URI'] = '/api/orcamentos';
    }

    protected function tearDown(): void
    {
        ApplicationContainer::flush();
        unset($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
        $_GET = [];
        $_POST = [];
        $this->resetSessionState();

        parent::tearDown();
    }

    public function testIndexThrowsAuthExceptionWhenSessionIsMissing(): void
    {
        $controller = $this->buildController();

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Não autenticado');

        $controller->index();
    }

    public function testStoreReturnsServiceResultPayload(): void
    {
        $this->seedAuthenticatedUserSession(33, 'Orcamento User');
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'categoria_id' => 10,
            'valor' => 250.0,
        ];

        $saveUseCase = Mockery::mock(SaveOrcamentoUseCase::class);
        $saveUseCase
            ->shouldReceive('execute')
            ->once()
            ->with(33, [
                'categoria_id' => 10,
                'valor' => 250.0,
            ])
            ->andReturn(ServiceResultDTO::ok('Orcamento salvo', ['id' => 101]));

        $controller = $this->buildController(saveOrcamentoUseCase: $saveUseCase);

        $response = $controller->store();
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame([
            'success' => true,
            'message' => 'Orcamento salvo',
            'data' => ['id' => 101],
        ], $payload);
    }

    public function testDestroyReturnsErrorResponseWhenUseCaseThrows(): void
    {
        $this->seedAuthenticatedUserSession(33, 'Orcamento User');
        $_SERVER['REQUEST_METHOD'] = 'DELETE';
        $_SERVER['REQUEST_URI'] = '/api/orcamentos/9';

        $deleteUseCase = Mockery::mock(DeleteOrcamentoUseCase::class);
        $deleteUseCase
            ->shouldReceive('execute')
            ->once()
            ->with(33, 9)
            ->andThrow(new \RuntimeException('boom'));

        $controller = $this->buildController(deleteOrcamentoUseCase: $deleteUseCase);

        $response = $controller->destroy(9);
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertFalse($payload['success']);
        $this->assertStringContainsString('Erro ao remover', $payload['message']);
        $this->assertArrayHasKey('error_id', $payload);
        $this->assertArrayHasKey('request_id', $payload);
    }

    public function testConstructorResolvesUseCasesFromContainerWhenAvailable(): void
    {
        $saveOrcamentoUseCase = Mockery::mock(SaveOrcamentoUseCase::class);
        $bulkSaveOrcamentosUseCase = Mockery::mock(BulkSaveOrcamentosUseCase::class);
        $deleteOrcamentoUseCase = Mockery::mock(DeleteOrcamentoUseCase::class);
        $getOrcamentoSugestoesUseCase = Mockery::mock(GetOrcamentoSugestoesUseCase::class);
        $applyOrcamentoSugestoesUseCase = Mockery::mock(ApplyOrcamentoSugestoesUseCase::class);
        $copyOrcamentosMesUseCase = Mockery::mock(CopyOrcamentosMesUseCase::class);
        $getOrcamentosListUseCase = Mockery::mock(GetOrcamentosListUseCase::class);

        $container = new IlluminateContainer();
        $container->instance(SaveOrcamentoUseCase::class, $saveOrcamentoUseCase);
        $container->instance(BulkSaveOrcamentosUseCase::class, $bulkSaveOrcamentosUseCase);
        $container->instance(DeleteOrcamentoUseCase::class, $deleteOrcamentoUseCase);
        $container->instance(GetOrcamentoSugestoesUseCase::class, $getOrcamentoSugestoesUseCase);
        $container->instance(ApplyOrcamentoSugestoesUseCase::class, $applyOrcamentoSugestoesUseCase);
        $container->instance(CopyOrcamentosMesUseCase::class, $copyOrcamentosMesUseCase);
        $container->instance(GetOrcamentosListUseCase::class, $getOrcamentosListUseCase);
        ApplicationContainer::setInstance($container);

        $controller = new OrcamentosController();

        $this->assertSame($saveOrcamentoUseCase, $this->readProperty($controller, 'saveOrcamentoUseCase'));
        $this->assertSame($bulkSaveOrcamentosUseCase, $this->readProperty($controller, 'bulkSaveOrcamentosUseCase'));
        $this->assertSame($deleteOrcamentoUseCase, $this->readProperty($controller, 'deleteOrcamentoUseCase'));
        $this->assertSame($getOrcamentoSugestoesUseCase, $this->readProperty($controller, 'getOrcamentoSugestoesUseCase'));
        $this->assertSame($applyOrcamentoSugestoesUseCase, $this->readProperty($controller, 'applyOrcamentoSugestoesUseCase'));
        $this->assertSame($copyOrcamentosMesUseCase, $this->readProperty($controller, 'copyOrcamentosMesUseCase'));
        $this->assertSame($getOrcamentosListUseCase, $this->readProperty($controller, 'getOrcamentosListUseCase'));
    }

    private function buildController(
        ?SaveOrcamentoUseCase $saveOrcamentoUseCase = null,
        ?BulkSaveOrcamentosUseCase $bulkSaveOrcamentosUseCase = null,
        ?DeleteOrcamentoUseCase $deleteOrcamentoUseCase = null,
        ?GetOrcamentoSugestoesUseCase $getOrcamentoSugestoesUseCase = null,
        ?ApplyOrcamentoSugestoesUseCase $applyOrcamentoSugestoesUseCase = null,
        ?CopyOrcamentosMesUseCase $copyOrcamentosMesUseCase = null,
        ?GetOrcamentosListUseCase $getOrcamentosListUseCase = null
    ): OrcamentosController {
        return new OrcamentosController(
            $saveOrcamentoUseCase ?? Mockery::mock(SaveOrcamentoUseCase::class),
            $bulkSaveOrcamentosUseCase ?? Mockery::mock(BulkSaveOrcamentosUseCase::class),
            $deleteOrcamentoUseCase ?? Mockery::mock(DeleteOrcamentoUseCase::class),
            $getOrcamentoSugestoesUseCase ?? Mockery::mock(GetOrcamentoSugestoesUseCase::class),
            $applyOrcamentoSugestoesUseCase ?? Mockery::mock(ApplyOrcamentoSugestoesUseCase::class),
            $copyOrcamentosMesUseCase ?? Mockery::mock(CopyOrcamentosMesUseCase::class),
            $getOrcamentosListUseCase ?? Mockery::mock(GetOrcamentosListUseCase::class),
        );
    }

    private function readProperty(object $object, string $property): mixed
    {
        $reflection = new \ReflectionProperty($object, $property);
        $reflection->setAccessible(true);

        return $reflection->getValue($object);
    }

    private function seedAuthenticatedUserSession(int $userId, string $name): void
    {
        $this->startIsolatedSession('orcamentos-controller-test');

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
}
