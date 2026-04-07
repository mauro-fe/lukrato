<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api\Fatura;

use Application\Controllers\Api\Fatura\FaturasController;
use Application\Core\Exceptions\AuthException;
use Application\Models\Usuario;
use Application\Services\Fatura\FaturaService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tests\Support\SessionIsolation;

class FaturasControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use SessionIsolation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetSessionState();
        $_GET = [];
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    protected function tearDown(): void
    {
        unset($_SERVER['REQUEST_METHOD']);
        $_GET = [];
        $_POST = [];
        $this->resetSessionState();

        parent::tearDown();
    }

    public function testIndexValidatesMonthRangeBeforeCallingService(): void
    {
        $this->seedAuthenticatedUserSession(200, 'Fatura User');
        $_GET['mes'] = '13';

        $service = Mockery::mock(FaturaService::class);
        $controller = new FaturasController($service);

        $response = $controller->index();

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'Mês inválido. Deve estar entre 1 e 12',
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testShowReturnsNotFoundWhenServiceDoesNotFindOwnedFatura(): void
    {
        $this->seedAuthenticatedUserSession(200, 'Fatura User');

        $service = Mockery::mock(FaturaService::class);
        $service
            ->shouldReceive('buscar')
            ->once()
            ->with(10, 200)
            ->andReturnNull();

        $controller = new FaturasController($service);
        $response = $controller->show(10);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'Fatura não encontrada',
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testStoreThrowsAuthExceptionWhenSessionIsMissing(): void
    {
        $controller = new FaturasController(Mockery::mock(FaturaService::class));

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Não autenticado');

        $controller->store();
    }

    private function seedAuthenticatedUserSession(int $userId, string $name): void
    {
        $this->startIsolatedSession('faturas-controller-test');

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
