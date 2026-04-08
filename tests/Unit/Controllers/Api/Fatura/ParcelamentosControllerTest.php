<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api\Fatura;

use Application\Controllers\Api\Fatura\ParcelamentosController;
use Application\Core\Exceptions\AuthException;
use Application\Repositories\CategoriaRepository;
use Application\Repositories\ContaRepository;
use Application\Repositories\ParcelamentoRepository;
use Application\Models\Usuario;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tests\Support\SessionIsolation;

class ParcelamentosControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use SessionIsolation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetSessionState();
        $_GET = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    protected function tearDown(): void
    {
        unset($_SERVER['REQUEST_METHOD']);
        $_GET = [];
        $this->resetSessionState();

        parent::tearDown();
    }

    public function testShowReturnsNotFoundResponseWhenParcelamentoDoesNotExist(): void
    {
        $this->seedAuthenticatedUserSession(21, 'Parcelamento User');

        $parcelamentoRepo = Mockery::mock(ParcelamentoRepository::class);
        $parcelamentoRepo
            ->shouldReceive('findWithLancamentos')
            ->once()
            ->with(55)
            ->andReturnNull();

        $controller = $this->buildController(parcelamentoRepo: $parcelamentoRepo);

        $response = $controller->show(55);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'Parcelamento não encontrado',
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testStoreReturnsBadRequestWhenJsonPayloadIsMissing(): void
    {
        $this->seedAuthenticatedUserSession(22, 'Parcelamento Store');
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $controller = $this->buildController();

        $response = $controller->store();

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'Dados inválidos',
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testIndexThrowsAuthExceptionWhenSessionIsMissing(): void
    {
        $controller = $this->buildController();

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Não autenticado');

        $controller->index();
    }

    private function buildController(
        ?ParcelamentoRepository $parcelamentoRepo = null,
        ?CategoriaRepository $categoriaRepo = null,
        ?ContaRepository $contaRepo = null
    ): ParcelamentosController {
        return new ParcelamentosController(
            $parcelamentoRepo ?? Mockery::mock(ParcelamentoRepository::class),
            $categoriaRepo ?? Mockery::mock(CategoriaRepository::class),
            $contaRepo ?? Mockery::mock(ContaRepository::class),
        );
    }

    private function seedAuthenticatedUserSession(int $userId, string $name): void
    {
        $this->startIsolatedSession('parcelamentos-controller-test');

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
