<?php

declare(strict_types=1);

namespace Tests\Unit\UseCases\Lancamentos;

use Application\Models\Lancamento;
use Application\Repositories\ContaRepository;
use Application\Repositories\LancamentoRepository;
use Application\Services\Metas\MetaProgressService;
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

    public function testExecuteKeepsExistingDecimalStringValorWhenPayloadOmitsValor(): void
    {
        $lancamentoRepo = Mockery::mock(LancamentoRepository::class);
        $contaRepo = Mockery::mock(ContaRepository::class);
        $metaProgressService = Mockery::mock(MetaProgressService::class);

        $contaRepo->shouldReceive('belongsToUser')->twice()->andReturnTrue();
        $lancamentoRepo->shouldReceive('update')->once()->with(99, Mockery::on(
            static fn(array $data): bool => abs((float) ($data['valor'] ?? 0) - 99.90) < 0.0001
                && ($data['observacao'] ?? null) === 'Ajuste'
        ))->andReturnTrue();

        $updated = Mockery::mock(Lancamento::class)->makePartial();
        $updated->setRawAttributes([
            'id' => 99,
            'tipo' => 'transferencia',
            'data' => '2026-03-31',
            'valor' => '99.90',
            'descricao' => 'Transferencia: Conta A -> Conta B',
            'observacao' => 'Ajuste',
            'conta_id' => 1,
            'conta_id_destino' => 2,
            'eh_transferencia' => 1,
            'pago' => 1,
            'meta_id' => null,
            'meta_operacao' => null,
            'meta_valor' => null,
            'categoria_id' => null,
            'subcategoria_id' => null,
        ], true);
        $updated->shouldReceive('loadMissing')->once()->with(['categoria', 'conta', 'subcategoria', 'meta'])->andReturnSelf();

        $lancamentoRepo->shouldReceive('findByIdAndUser')->once()->with(99, 10)->andReturn($updated);
        $metaProgressService->shouldNotReceive('recalculateMeta');

        $useCase = new UpdateTransferenciaUseCase($lancamentoRepo, $contaRepo, $metaProgressService);

        $lancamento = (object) [
            'id' => 99,
            'data' => '2026-03-31',
            'valor' => '99.90',
            'conta_id' => 1,
            'conta_id_destino' => 2,
            'meta_id' => null,
            'meta_operacao' => null,
            'meta_valor' => null,
            'descricao' => 'Transferencia: Conta A -> Conta B',
            'observacao' => null,
        ];

        $result = $useCase->execute(10, $lancamento, [
            'descricao' => 'Transferencia: Conta A -> Conta B',
            'observacao' => 'Ajuste',
        ]);

        $this->assertFalse($result->isError());
        $this->assertSame('Lancamento atualizado', $result->message);
    }
}
