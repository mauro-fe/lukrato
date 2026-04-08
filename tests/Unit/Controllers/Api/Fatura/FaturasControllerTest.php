<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api\Fatura;

use Application\Controllers\Api\Fatura\FaturasController;
use Application\Core\Exceptions\AuthException;
use Application\Models\Usuario;
use Application\Services\Fatura\FaturaApiWorkflowService;
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

        $workflowService = Mockery::mock(FaturaApiWorkflowService::class);
        $workflowService
            ->shouldReceive('listInvoices')
            ->once()
            ->with(200, [
                'cartao_id' => null,
                'status' => null,
                'mes' => '13',
                'ano' => null,
            ])
            ->andReturn([
                'success' => false,
                'status' => 400,
                'message' => 'Mês inválido. Deve estar entre 1 e 12',
            ]);

        $controller = new FaturasController($workflowService);

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

        $workflowService = Mockery::mock(FaturaApiWorkflowService::class);
        $workflowService
            ->shouldReceive('showInvoice')
            ->once()
            ->with(10, 200)
            ->andReturn([
                'success' => false,
                'status' => 404,
                'message' => 'Fatura não encontrada',
            ]);

        $controller = new FaturasController($workflowService);
        $response = $controller->show(10);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'Fatura não encontrada',
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testStoreThrowsAuthExceptionWhenSessionIsMissing(): void
    {
        $controller = new FaturasController(Mockery::mock(FaturaApiWorkflowService::class));

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
