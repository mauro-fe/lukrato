<?php

declare(strict_types=1);

namespace Tests\Unit\UseCases\Lancamentos;

use Application\Repositories\ContaRepository;
use Application\Repositories\LancamentoRepository;
use Application\Services\Financeiro\MetaProgressService;
use Application\UseCases\Lancamentos\UpdateTransferenciaUseCase;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class UpdateTransferenciaUseCaseTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testExecuteReturnsValidationErrorWhenOriginAndDestinationAccountsAreTheSame(): void
    {
        $lancamentoRepo = Mockery::mock(LancamentoRepository::class);
        $contaRepo = Mockery::mock(ContaRepository::class);
        $metaProgressService = Mockery::mock(MetaProgressService::class);

        $contaRepo->shouldReceive('belongsToUser')->twice()->andReturnTrue();
        $lancamentoRepo->shouldNotReceive('update');
        $lancamentoRepo->shouldNotReceive('findByIdAndUser');
        $metaProgressService->shouldNotReceive('recalculateMeta');

        $useCase = new UpdateTransferenciaUseCase($lancamentoRepo, $contaRepo, $metaProgressService);

        $lancamento = (object) [
            'id' => 99,
            'data' => '2026-03-31',
            'valor' => 120.0,
            'conta_id' => 1,
            'conta_id_destino' => 2,
            'meta_id' => null,
            'meta_operacao' => null,
            'meta_valor' => null,
            'observacao' => null,
        ];

        $result = $useCase->execute(10, $lancamento, [
            'data' => '2026-03-31',
            'valor' => 120.0,
            'conta_id' => 5,
            'conta_id_destino' => 5,
        ]);

        $this->assertTrue($result->isValidationError());
        $this->assertSame(
            'A conta de destino deve ser diferente da origem.',
            $result->data['errors']['conta_id_destino'] ?? null
        );
    }
}
