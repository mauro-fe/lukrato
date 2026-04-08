<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api\Lancamentos;

use Application\Controllers\Api\Lancamentos\TransactionsController;
use Application\DTO\ServiceResultDTO;
use Application\Models\Usuario;
use Application\UseCases\Lancamentos\CreateLancamentoUseCase;
use Application\UseCases\Lancamentos\CreateTransferenciaUseCase;
use Application\UseCases\Lancamentos\UpdateLancamentoUseCase;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tests\Support\SessionIsolation;

class TransactionsControllerTest extends TestCase
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

        $createLancamentoUseCase = Mockery::mock(CreateLancamentoUseCase::class);
        $createLancamentoUseCase
            ->shouldReceive('execute')
            ->once()
            ->with(77, [])
            ->andReturn(ServiceResultDTO::validationFail([
                'tipo' => 'Obrigatorio',
                'data' => 'Obrigatoria',
                'valor' => 'Obrigatorio',
                'descricao' => 'Obrigatoria',
                'conta_id' => 'Obrigatoria',
            ]));

        $controller = $this->buildController(createLancamentoUseCase: $createLancamentoUseCase);

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

        $updateLancamentoUseCase = Mockery::mock(UpdateLancamentoUseCase::class);
        $updateLancamentoUseCase
            ->shouldReceive('execute')
            ->once()
            ->with(88, 999, [])
            ->andReturn(ServiceResultDTO::fail('Lancamento nao encontrado.', 404));

        $controller = $this->buildController(
            updateLancamentoUseCase: $updateLancamentoUseCase,
        );

        $response = $controller->update(999);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'Lancamento nao encontrado.',
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    private function buildController(
        ?CreateLancamentoUseCase $createLancamentoUseCase = null,
        ?UpdateLancamentoUseCase $updateLancamentoUseCase = null,
        ?CreateTransferenciaUseCase $createTransferenciaUseCase = null,
    ): TransactionsController {
        return new TransactionsController(
            $createLancamentoUseCase ?? Mockery::mock(CreateLancamentoUseCase::class),
            $updateLancamentoUseCase ?? Mockery::mock(UpdateLancamentoUseCase::class),
            $createTransferenciaUseCase ?? Mockery::mock(CreateTransferenciaUseCase::class),
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
