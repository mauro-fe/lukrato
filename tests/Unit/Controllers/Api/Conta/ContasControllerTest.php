<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api\Conta;

use Application\Controllers\Api\Conta\ContasController;
use Application\Core\Exceptions\AuthException;
use Application\Models\Usuario;
use Application\Services\Conta\ContaApiWorkflowService;
use Application\Services\Demo\DemoPreviewService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tests\Support\SessionIsolation;

class ContasControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use SessionIsolation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetSessionState();
        $_GET = [];
        $_POST = [];
        $_REQUEST = [];
    }

    protected function tearDown(): void
    {
        $_GET = [];
        $_POST = [];
        $_REQUEST = [];
        $this->resetSessionState();
        parent::tearDown();
    }

    public function testIndexReturnsSuccessResponse(): void
    {
        $this->seedAuthenticatedSession(1101, 'Conta User');

        $workflowService = Mockery::mock(ContaApiWorkflowService::class);
        $workflowService
            ->shouldReceive('listAccounts')
            ->once()
            ->with(1101, [
                'archived' => 0,
                'only_active' => null,
                'with_balances' => 0,
                'month' => null,
            ])
            ->andReturn([
                'success' => true,
                'data' => [
                    ['id' => 1, 'nome' => 'Conta Principal'],
                ],
            ]);

        $controller = new ContasController($workflowService, Mockery::mock(DemoPreviewService::class));

        $response = $controller->index();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'success' => true,
            'message' => 'Success',
            'data' => [
                ['id' => 1, 'nome' => 'Conta Principal'],
            ],
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testStoreReturnsForbiddenWhenPlanLimitIsReached(): void
    {
        $this->seedAuthenticatedSession(1102, 'Conta User');

        $workflowService = Mockery::mock(ContaApiWorkflowService::class);
        $workflowService
            ->shouldReceive('createAccount')
            ->once()
            ->with(1102, [])
            ->andReturn([
                'success' => false,
                'status' => 403,
                'message' => 'Limite atingido',
                'errors' => [
                    'limit_reached' => true,
                    'upgrade_url' => '/upgrade',
                    'limit_info' => [
                        'limit' => 2,
                        'used' => 2,
                        'remaining' => 0,
                    ],
                ],
            ]);

        $controller = new ContasController($workflowService, Mockery::mock(DemoPreviewService::class));

        $response = $controller->store();

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'Limite atingido',
            'errors' => [
                'limit_reached' => true,
                'upgrade_url' => '/upgrade',
                'limit_info' => [
                    'limit' => 2,
                    'used' => 2,
                    'remaining' => 0,
                ],
            ],
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testCreateInstituicaoReturnsBadRequestWhenNameIsMissing(): void
    {
        $workflowService = Mockery::mock(ContaApiWorkflowService::class);
        $workflowService
            ->shouldReceive('createInstituicao')
            ->once()
            ->with([])
            ->andReturn([
                'success' => false,
                'status' => 400,
                'message' => 'Nome da instituição é obrigatório',
            ]);

        $controller = new ContasController($workflowService, Mockery::mock(DemoPreviewService::class));

        $response = $controller->createInstituicao();

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'Nome da instituição é obrigatório',
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testIndexThrowsAuthExceptionWhenSessionIsMissing(): void
    {
        $controller = new ContasController(
            Mockery::mock(ContaApiWorkflowService::class),
            Mockery::mock(DemoPreviewService::class),
        );

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Não autenticado');

        $controller->index();
    }

    private function seedAuthenticatedSession(int $userId, string $name): void
    {
        $this->startIsolatedSession('contas-controller-test');

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
}
