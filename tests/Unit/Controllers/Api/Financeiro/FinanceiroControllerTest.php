<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api\Financeiro;

use Application\Controllers\Api\Financeiro\FinanceiroController;
use Application\Core\Exceptions\AuthException;
use Application\Repositories\CategoriaRepository;
use Application\Repositories\ContaRepository;
use Application\Repositories\LancamentoRepository;
use Application\Services\Conta\TransferenciaService;
use Application\Services\Lancamento\LancamentoLimitService;
use Application\Models\Usuario;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tests\Support\SessionIsolation;

class FinanceiroControllerTest extends TestCase
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

    public function testStoreReturnsValidationResponseWhenPayloadIsInvalid(): void
    {
        $this->seedAuthenticatedUserSession(77, 'Financeiro User');
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $controller = $this->buildController();

        $response = $controller->store();
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertFalse($payload['success']);
        $this->assertSame('Validation failed', $payload['message']);
        $this->assertArrayHasKey('tipo', $payload['errors']);
        $this->assertArrayHasKey('data', $payload['errors']);
        $this->assertArrayHasKey('valor', $payload['errors']);
        $this->assertArrayHasKey('descricao', $payload['errors']);
        $this->assertArrayHasKey('conta_id', $payload['errors']);
    }

    public function testUpdateReturnsNotFoundResponseWhenLancamentoDoesNotExist(): void
    {
        $this->seedAuthenticatedUserSession(88, 'Financeiro Update');

        $lancamentoRepo = Mockery::mock(LancamentoRepository::class);
        $lancamentoRepo
            ->shouldReceive('findByIdAndUser')
            ->once()
            ->with(999, 88)
            ->andReturnNull();

        $controller = $this->buildController(
            lancamentoRepo: $lancamentoRepo,
        );

        $response = $controller->update(999);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'Lancamento nao encontrado.',
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testMetricsThrowsAuthExceptionWhenSessionIsMissing(): void
    {
        $controller = $this->buildController();

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Nao autenticado');

        $controller->metrics();
    }

    private function buildController(
        ?LancamentoLimitService $limitService = null,
        ?TransferenciaService $transferenciaService = null,
        ?LancamentoRepository $lancamentoRepo = null,
        ?CategoriaRepository $categoriaRepo = null,
        ?ContaRepository $contaRepo = null,
    ): FinanceiroController {
        return new FinanceiroController(
            $limitService ?? Mockery::mock(LancamentoLimitService::class),
            $transferenciaService ?? Mockery::mock(TransferenciaService::class),
            $lancamentoRepo ?? Mockery::mock(LancamentoRepository::class),
            $categoriaRepo ?? Mockery::mock(CategoriaRepository::class),
            $contaRepo ?? Mockery::mock(ContaRepository::class),
        );
    }

    private function seedAuthenticatedUserSession(int $userId, string $name): void
    {
        $this->startIsolatedSession('financeiro-controller-test');

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
