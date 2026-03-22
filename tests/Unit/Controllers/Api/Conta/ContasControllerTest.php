<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api\Conta;

use Application\Controllers\Api\Conta\ContasController;
use Application\Core\Exceptions\AuthException;
use Application\Models\Usuario;
use Application\Services\Conta\ContaService;
use Application\Services\Plan\PlanLimitService;
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

        $service = Mockery::mock(ContaService::class);
        $service
            ->shouldReceive('listarContas')
            ->once()
            ->with(1101, false, true, false, null)
            ->andReturn([
                ['id' => 1, 'nome' => 'Conta Principal'],
            ]);

        $controller = new ContasController($service, Mockery::mock(PlanLimitService::class));

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

        $planLimitService = Mockery::mock(PlanLimitService::class);
        $planLimitService
            ->shouldReceive('canCreateConta')
            ->once()
            ->with(1102)
            ->andReturn([
                'allowed' => false,
                'message' => 'Limite atingido',
                'upgrade_url' => '/upgrade',
                'limit' => 2,
                'used' => 2,
                'remaining' => 0,
            ]);

        $service = Mockery::mock(ContaService::class);

        $controller = new ContasController($service, $planLimitService);

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
        $controller = new ContasController(
            Mockery::mock(ContaService::class),
            Mockery::mock(PlanLimitService::class),
        );

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
            Mockery::mock(ContaService::class),
            Mockery::mock(PlanLimitService::class),
        );

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Nao autenticado');

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
