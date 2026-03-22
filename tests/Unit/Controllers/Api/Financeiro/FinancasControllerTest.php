<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api\Financeiro;

use Application\Controllers\Api\Financeiro\FinancasController;
use Application\Core\Exceptions\AuthException;
use Application\Services\Financeiro\MetaService;
use Application\Services\Financeiro\OrcamentoService;
use Application\Models\Usuario;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tests\Support\SessionIsolation;

class FinancasControllerTest extends TestCase
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

    public function testMetasStoreReturnsValidationResponseWhenPayloadIsInvalid(): void
    {
        $this->seedAuthenticatedUserSession(11, 'Financas User');
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $controller = $this->buildController();

        $response = $controller->metasStore();
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertFalse($payload['success']);
        $this->assertSame('Validation failed', $payload['message']);
        $this->assertArrayHasKey('titulo', $payload['errors']);
        $this->assertArrayHasKey('valor_alvo', $payload['errors']);
    }

    public function testMetasUpdateReturnsNotFoundResponseWhenServiceDoesNotFindMeta(): void
    {
        $this->seedAuthenticatedUserSession(12, 'Financas Update');
        $_SERVER['REQUEST_METHOD'] = 'PUT';

        $metaService = Mockery::mock(MetaService::class);
        $metaService
            ->shouldReceive('atualizar')
            ->once()
            ->with(12, 99, [])
            ->andReturnNull();

        $controller = $this->buildController(metaService: $metaService);

        $response = $controller->metasUpdate(99);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'Meta não encontrada.',
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testResumoThrowsAuthExceptionWhenSessionIsMissing(): void
    {
        $controller = $this->buildController();

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Nao autenticado');

        $controller->resumo();
    }

    private function buildController(
        ?MetaService $metaService = null,
        ?OrcamentoService $orcamentoService = null
    ): FinancasController {
        return new FinancasController(
            $metaService ?? Mockery::mock(MetaService::class),
            $orcamentoService ?? Mockery::mock(OrcamentoService::class),
        );
    }

    private function seedAuthenticatedUserSession(int $userId, string $name): void
    {
        $this->startIsolatedSession('financas-controller-test');

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
