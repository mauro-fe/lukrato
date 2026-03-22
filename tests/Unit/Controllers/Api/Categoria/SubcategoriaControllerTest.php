<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api\Categoria;

use Application\Controllers\Api\Categoria\SubcategoriaController;
use Application\Core\Exceptions\AuthException;
use Application\Services\Categoria\SubcategoriaService;
use Application\Models\Usuario;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tests\Support\SessionIsolation;

class SubcategoriaControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use SessionIsolation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetSessionState();
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    protected function tearDown(): void
    {
        $_POST = [];
        unset($_SERVER['REQUEST_METHOD']);
        $this->resetSessionState();
        parent::tearDown();
    }

    public function testIndexReturnsValidationErrorWhenCategoriaIdIsInvalid(): void
    {
        $this->seedAuthenticatedUserSession(81, 'Subcategoria User');

        $controller = new SubcategoriaController(Mockery::mock(SubcategoriaService::class));

        $response = $controller->index('abc');

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => [
                'categoria_id' => 'ID da categoria inválido.',
            ],
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testGroupedReturnsSuccessResponse(): void
    {
        $this->seedAuthenticatedUserSession(82, 'Subcategoria Grouped');

        $service = Mockery::mock(SubcategoriaService::class);
        $service
            ->shouldReceive('listAllGrouped')
            ->once()
            ->with(82)
            ->andReturn([['id' => 10, 'nome' => 'Alimentacao']]);

        $controller = new SubcategoriaController($service);

        $response = $controller->grouped();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'success' => true,
            'message' => 'Success',
            'data' => [['id' => 10, 'nome' => 'Alimentacao']],
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testStoreReturnsForbiddenResponseWhenServiceRejectsPlanLimit(): void
    {
        $this->seedAuthenticatedUserSession(83, 'Subcategoria Limit');
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['nome' => 'Cinema'];

        $service = Mockery::mock(SubcategoriaService::class);
        $service
            ->shouldReceive('create')
            ->once()
            ->andThrow(new \DomainException('Limite de subcategorias atingido.'));

        $controller = new SubcategoriaController($service);

        $response = $controller->store(7);

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'Limite de subcategorias atingido.',
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testUpdateThrowsAuthExceptionWhenSessionIsMissing(): void
    {
        $controller = new SubcategoriaController(Mockery::mock(SubcategoriaService::class));

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Nao autenticado');

        $controller->update(1);
    }

    private function seedAuthenticatedUserSession(int $userId, string $name): void
    {
        $this->startIsolatedSession('subcategoria-controller-test');

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
