<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api\Lancamentos;

use Application\Controllers\Api\Lancamentos\CancelarRecorrenciaController;
use Application\Controllers\Api\Lancamentos\DestroyController;
use Application\Controllers\Api\Lancamentos\ExportController;
use Application\Controllers\Api\Lancamentos\FaturaDetalhesController;
use Application\Controllers\Api\Lancamentos\IndexController;
use Application\Controllers\Api\Lancamentos\MarcarPagoController;
use Application\Controllers\Api\Lancamentos\StoreController;
use Application\Controllers\Api\Lancamentos\UpdateController;
use Application\Controllers\Api\Lancamentos\UsageController;
use Application\Core\Exceptions\AuthException;
use Application\DTO\ServiceResultDTO;
use Application\Models\Usuario;
use Application\Repositories\ContaRepository;
use Application\Repositories\FaturaCartaoRepository;
use Application\Repositories\LancamentoRepository;
use Application\Repositories\ParcelamentoRepository;
use Application\Services\Lancamento\LancamentoCreationService;
use Application\Services\Lancamento\LancamentoExportService;
use Application\Services\Lancamento\LancamentoLimitService;
use Application\Services\Lancamento\LancamentoUpdateService;
use Application\UseCases\Lancamentos\BulkDeleteLancamentosUseCase;
use Application\UseCases\Lancamentos\DeleteLancamentoUseCase;
use Application\UseCases\Lancamentos\ToggleLancamentoPagoUseCase;
use Application\UseCases\Lancamentos\UpdateTransferenciaUseCase;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tests\Support\SessionIsolation;

class LancamentosApiControllersTest extends TestCase
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

    public function testCancelarRecorrenciaControllerReturnsServicePayload(): void
    {
        $this->seedAuthenticatedUserSession(100, 'Lancamentos User');

        $creationService = Mockery::mock(LancamentoCreationService::class);
        $creationService
            ->shouldReceive('cancelarRecorrencia')
            ->once()
            ->with(9, 100)
            ->andReturn(ServiceResultDTO::ok('Recorrencia cancelada', ['cancelados' => 3]));

        $controller = new CancelarRecorrenciaController($creationService);
        $response = $controller->__invoke(9);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'success' => true,
            'message' => 'Recorrencia cancelada',
            'data' => ['cancelados' => 3],
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testDestroyControllerReturnsNotFoundWhenLancamentoIsMissing(): void
    {
        $this->seedAuthenticatedUserSession(100, 'Lancamentos User');

        $deleteUseCase = Mockery::mock(DeleteLancamentoUseCase::class);
        $deleteUseCase
            ->shouldReceive('execute')
            ->once()
            ->with(100, 5, 'single')
            ->andReturn(ServiceResultDTO::fail('Lancamento nao encontrado', 404));

        $controller = new DestroyController(deleteUseCase: $deleteUseCase);
        $response = $controller->__invoke(5);

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testDestroyControllerInvokeMapsUseCaseSuccessToApiPayload(): void
    {
        $this->seedAuthenticatedUserSession(100, 'Lancamentos User');

        $deleteUseCase = Mockery::mock(DeleteLancamentoUseCase::class);
        $deleteUseCase
            ->shouldReceive('execute')
            ->once()
            ->with(100, 5, 'single')
            ->andReturn(ServiceResultDTO::ok('Lancamento excluido', [
                'ok' => true,
                'message' => 'Lancamento excluido',
                'excluidos' => 1,
            ]));

        $controller = new DestroyController(deleteUseCase: $deleteUseCase);

        $response = $controller->__invoke(5);
        $json = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(1, $json['data']['excluidos'] ?? null);
    }

    public function testDestroyControllerBulkDeleteReturnsValidationErrorWhenIdsAreMissing(): void
    {
        $this->seedAuthenticatedUserSession(100, 'Lancamentos User');
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $bulkDeleteUseCase = Mockery::mock(BulkDeleteLancamentosUseCase::class);
        $bulkDeleteUseCase
            ->shouldReceive('execute')
            ->once()
            ->with(100, [])
            ->andReturn(ServiceResultDTO::fail('Nenhum lancamento selecionado.', 422));

        $controller = new DestroyController(bulkDeleteUseCase: $bulkDeleteUseCase);

        $response = $controller->bulkDelete();

        $this->assertSame(422, $response->getStatusCode());
    }

    public function testDestroyControllerBulkDeleteMapsUseCaseSuccessToApiPayload(): void
    {
        $this->seedAuthenticatedUserSession(100, 'Lancamentos User');
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['ids' => [1, 2]];

        $bulkDeleteUseCase = Mockery::mock(BulkDeleteLancamentosUseCase::class);
        $bulkDeleteUseCase
            ->shouldReceive('execute')
            ->once()
            ->with(100, [1, 2])
            ->andReturn(ServiceResultDTO::ok('Bulk delete concluido', [
                'deleted' => 2,
                'errors' => [],
                'message' => '2 lancamentos excluidos com sucesso.',
            ]));

        $controller = new DestroyController(bulkDeleteUseCase: $bulkDeleteUseCase);

        $response = $controller->bulkDelete();
        $json = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(2, $json['data']['deleted'] ?? null);
        $this->assertSame([], $json['data']['errors'] ?? null);
    }

    public function testExportControllerThrowsAuthExceptionWhenSessionIsMissing(): void
    {
        $controller = new ExportController(Mockery::mock(LancamentoExportService::class));

        $this->expectException(AuthException::class);
        $this->expectExceptionMessageMatches('/N[ãa]o autenticado/u');

        $controller->__invoke();
    }

    public function testFaturaDetalhesControllerReturnsNotFoundWhenLancamentoIsMissing(): void
    {
        $this->seedAuthenticatedUserSession(100, 'Lancamentos User');

        $repo = Mockery::mock(LancamentoRepository::class);
        $repo->shouldReceive('findByIdAndUser')->once()->with(7, 100)->andReturnNull();

        $controller = new FaturaDetalhesController($repo, Mockery::mock(FaturaCartaoRepository::class));
        $response = $controller->__invoke(7);

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testIndexControllerThrowsAuthExceptionWhenSessionIsMissing(): void
    {
        $controller = new IndexController(Mockery::mock(LancamentoRepository::class));

        $this->expectException(AuthException::class);
        $this->expectExceptionMessageMatches('/N[ãa]o autenticado/u');

        $controller->__invoke();
    }

    public function testMarcarPagoControllerReturnsNotFoundWhenLancamentoIsMissing(): void
    {
        $this->seedAuthenticatedUserSession(100, 'Lancamentos User');

        $useCase = Mockery::mock(ToggleLancamentoPagoUseCase::class);
        $useCase
            ->shouldReceive('execute')
            ->once()
            ->with(100, 8, true)
            ->andReturn(ServiceResultDTO::fail('Lancamento nao encontrado', 404));

        $controller = new MarcarPagoController($useCase);

        $response = $controller->__invoke(8);

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testMarcarPagoControllerInvokeMapsUseCaseSuccessToApiPayload(): void
    {
        $this->seedAuthenticatedUserSession(100, 'Lancamentos User');

        $lancamento = Mockery::mock(\Application\Models\Lancamento::class)->makePartial();
        $lancamento->shouldReceive('loadMissing')->once()->with(['categoria', 'conta', 'parcelamento']);
        $lancamento->id = 8;
        $lancamento->data = null;
        $lancamento->tipo = 'despesa';
        $lancamento->valor = 10.5;
        $lancamento->descricao = 'Conta';
        $lancamento->conta_id = null;
        $lancamento->categoria_id = null;
        $lancamento->pago = true;

        $useCase = Mockery::mock(ToggleLancamentoPagoUseCase::class);
        $useCase
            ->shouldReceive('execute')
            ->once()
            ->with(100, 8, true)
            ->andReturn(ServiceResultDTO::ok('Lancamento marcado como pago.', [
                'lancamento' => $lancamento,
            ]));

        $controller = new MarcarPagoController($useCase);

        $response = $controller->__invoke(8);
        $json = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Lancamento marcado como pago.', $json['message'] ?? null);
    }

    public function testMarcarPagoControllerDesmarcarMapsUseCaseSuccessToApiPayload(): void
    {
        $this->seedAuthenticatedUserSession(100, 'Lancamentos User');

        $lancamento = Mockery::mock(\Application\Models\Lancamento::class)->makePartial();
        $lancamento->shouldReceive('loadMissing')->once()->with(['categoria', 'conta', 'parcelamento']);
        $lancamento->id = 9;
        $lancamento->data = null;
        $lancamento->tipo = 'despesa';
        $lancamento->valor = 21.0;
        $lancamento->descricao = 'Assinatura';
        $lancamento->conta_id = null;
        $lancamento->categoria_id = null;
        $lancamento->pago = false;

        $useCase = Mockery::mock(ToggleLancamentoPagoUseCase::class);
        $useCase
            ->shouldReceive('execute')
            ->once()
            ->with(100, 9, false)
            ->andReturn(ServiceResultDTO::ok('Lancamento marcado como pendente.', [
                'lancamento' => $lancamento,
            ]));

        $controller = new MarcarPagoController($useCase);

        $response = $controller->desmarcar(9);
        $json = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Lancamento marcado como pendente.', $json['message'] ?? null);
    }

    public function testStoreControllerMapsValidationResultToValidationResponse(): void
    {
        $this->seedAuthenticatedUserSession(100, 'Lancamentos User');
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $creationService = Mockery::mock(LancamentoCreationService::class);
        $creationService
            ->shouldReceive('createFromPayload')
            ->once()
            ->with(100, [])
            ->andReturn(ServiceResultDTO::validationFail(['descricao' => 'Obrigatoria']));

        $controller = new StoreController($creationService);
        $response = $controller->__invoke();

        $this->assertSame(422, $response->getStatusCode());
    }

    public function testUpdateControllerReturnsNotFoundWhenLancamentoIsMissing(): void
    {
        $this->seedAuthenticatedUserSession(100, 'Lancamentos User');

        $repo = Mockery::mock(LancamentoRepository::class);
        $repo->shouldReceive('findByIdAndUser')->once()->with(12, 100)->andReturnNull();

        $controller = new UpdateController(
            $repo,
            Mockery::mock(LancamentoUpdateService::class),
            Mockery::mock(UpdateTransferenciaUseCase::class),
        );

        $response = $controller->__invoke(12);

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testUsageControllerValidatesMonthFormat(): void
    {
        $this->seedAuthenticatedUserSession(100, 'Lancamentos User');
        $_GET['month'] = '2026-13';

        $controller = new UsageController(Mockery::mock(LancamentoLimitService::class));
        $response = $controller->__invoke();

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => ['month' => 'Formato inválido (YYYY-MM)'],
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    private function seedAuthenticatedUserSession(int $userId, string $name): void
    {
        $this->startIsolatedSession('lancamentos-api-controllers-test');

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
