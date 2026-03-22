<?php

declare(strict_types=1);

namespace Tests\Unit\Services\User;

use Application\DTO\ServiceResultDTO;
use Application\Models\OnboardingProgress;
use Application\Models\Usuario;
use Application\Services\Conta\ContaService;
use Application\Services\Lancamento\LancamentoCreationService;
use Application\Services\User\OnboardingProgressService;
use Application\Services\User\OnboardingWorkflowService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class OnboardingWorkflowServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testGetStatusReturnsNotFoundWhenUserDoesNotExist(): void
    {
        $service = $this->makeService(user: null);

        $result = $service->getStatus(10);

        $this->assertFalse($result['success']);
        $this->assertSame(404, $result['status']);
        $this->assertSame('Usuario nao encontrado', $result['message']);
    }

    public function testGetStatusReturnsFlagsFromPersistedProgress(): void
    {
        $user = new Usuario();
        $user->id = 20;

        $progress = new OnboardingProgress([
            'user_id' => 20,
            'has_conta' => true,
            'has_lancamento' => false,
            'onboarding_completed_at' => null,
        ]);

        $progressService = Mockery::mock(OnboardingProgressService::class);
        $progressService
            ->shouldReceive('getProgress')
            ->once()
            ->with(20)
            ->andReturn($progress);

        $service = $this->makeService(
            user: $user,
            progressService: $progressService
        );

        $result = $service->getStatus(20);

        $this->assertTrue($result['success']);
        $this->assertSame([
            'tem_conta' => true,
            'tem_lancamento' => false,
            'onboarding_completo' => false,
        ], $result['data']);
    }

    public function testStoreContaJsonReturnsValidationErrorWhenNameIsMissing(): void
    {
        $service = $this->makeService();

        $result = $service->storeContaJson(30, [
            'nome' => '',
            'instituicao_financeira_id' => '8',
            'saldo_inicial' => '100,00',
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame(400, $result['status']);
        $this->assertSame('O nome da conta e obrigatorio.', $result['message']);
    }

    public function testStoreLancamentoJsonReturnsFirstValidationMessageFromCreationService(): void
    {
        $creationService = Mockery::mock(LancamentoCreationService::class);
        $creationService
            ->shouldReceive('createFromPayload')
            ->once()
            ->with(40, Mockery::on(function (array $payload): bool {
                return $payload['tipo'] === 'transferencia'
                    && $payload['data'] === date('Y-m-d')
                    && $payload['pago'] === true;
            }))
            ->andReturn(ServiceResultDTO::validationFail([
                'tipo' => 'Tipo invalido.',
            ]));

        $service = $this->makeService(
            lancamentoCreationService: $creationService
        );

        $result = $service->storeLancamentoJson(40, [
            'tipo' => 'transferencia',
            'valor' => '10,00',
            'categoria_id' => '2',
            'descricao' => 'Teste',
            'conta_id' => '5',
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame(400, $result['status']);
        $this->assertSame('Tipo invalido.', $result['message']);
    }

    public function testCompleteRequiresAtLeastOneAccountInProgress(): void
    {
        $user = new class extends Usuario {
            public function save(array $options = []): bool
            {
                return true;
            }
        };
        $user->id = 50;

        $progress = new OnboardingProgress([
            'user_id' => 50,
            'has_conta' => false,
            'has_lancamento' => false,
        ]);

        $progressService = Mockery::mock(OnboardingProgressService::class);
        $progressService
            ->shouldReceive('getProgress')
            ->once()
            ->with(50)
            ->andReturn($progress);
        $progressService
            ->shouldReceive('syncFromDatabase')
            ->once()
            ->with(50)
            ->andReturn($progress);

        $service = $this->makeService(
            user: $user,
            progressService: $progressService
        );

        $result = $service->complete(50);

        $this->assertFalse($result['success']);
        $this->assertSame(422, $result['status']);
        $this->assertSame('Voce precisa criar pelo menos uma conta antes de continuar.', $result['message']);
    }

    public function testChecklistUsesPersistedProgressAndMetrics(): void
    {
        $progress = new OnboardingProgress([
            'user_id' => 60,
            'has_conta' => true,
            'has_lancamento' => true,
        ]);

        $progressService = Mockery::mock(OnboardingProgressService::class);
        $progressService
            ->shouldReceive('getProgress')
            ->once()
            ->with(60)
            ->andReturn($progress);
        $progressService
            ->shouldReceive('getChecklistMetrics')
            ->once()
            ->with(60)
            ->andReturn([
                'accounts_count' => 2,
                'entries_count' => 2,
                'categories_count' => 1,
                'has_meta' => true,
                'has_budget' => false,
            ]);

        $service = $this->makeService(
            progressService: $progressService
        );

        $result = $service->getChecklist(60);

        $this->assertTrue($result['success']);
        $this->assertSame(5, $result['data']['done_count']);
        $this->assertFalse($result['data']['all_complete']);
    }

    private function makeService(
        ?Usuario $user = null,
        ?ContaService $contaService = null,
        ?LancamentoCreationService $lancamentoCreationService = null,
        ?OnboardingProgressService $progressService = null
    ): OnboardingWorkflowService {
        $contaService ??= Mockery::mock(ContaService::class);
        $lancamentoCreationService ??= Mockery::mock(LancamentoCreationService::class);
        $progressService ??= Mockery::mock(OnboardingProgressService::class);

        return new class ($contaService, $lancamentoCreationService, $progressService, $user) extends OnboardingWorkflowService {
            public function __construct(
                ContaService $contaService,
                LancamentoCreationService $lancamentoCreationService,
                OnboardingProgressService $progressService,
                private readonly ?Usuario $user
            ) {
                parent::__construct($contaService, $lancamentoCreationService, $progressService);
            }

            protected function findUser(int $userId): ?Usuario
            {
                return $this->user;
            }
        };
    }
}
