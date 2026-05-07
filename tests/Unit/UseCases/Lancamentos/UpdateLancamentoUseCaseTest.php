<?php

declare(strict_types=1);

namespace Tests\Unit\UseCases\Lancamentos;

use Application\Models\Lancamento;
use Application\Repositories\CategoriaRepository;
use Application\Repositories\ContaRepository;
use Application\Repositories\LancamentoRepository;
use Application\Services\Metas\MetaProgressService;
use Application\UseCases\Lancamentos\UpdateLancamentoUseCase;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class UpdateLancamentoUseCaseTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testExecuteReturns422WhenIdIsInvalid(): void
    {
        $repo = Mockery::mock(LancamentoRepository::class);
        $categoriaRepo = Mockery::mock(CategoriaRepository::class);
        $contaRepo = Mockery::mock(ContaRepository::class);
        $metaProgressService = Mockery::mock(MetaProgressService::class);

        $repo->shouldNotReceive('findByIdAndUser');
        $repo->shouldNotReceive('update');
        $metaProgressService->shouldNotReceive('recalculateMeta');

        $useCase = new UpdateLancamentoUseCase($repo, $categoriaRepo, $contaRepo, $metaProgressService);
        $result = $useCase->execute(10, 0, []);

        $this->assertTrue($result->isError());
        $this->assertSame(422, $result->httpCode);
        $this->assertSame('ID inválido.', $result->message);
    }

    public function testExecuteReturns404WhenLancamentoDoesNotExist(): void
    {
        $repo = Mockery::mock(LancamentoRepository::class);
        $categoriaRepo = Mockery::mock(CategoriaRepository::class);
        $contaRepo = Mockery::mock(ContaRepository::class);
        $metaProgressService = Mockery::mock(MetaProgressService::class);

        $repo->shouldReceive('findByIdAndUser')->once()->with(99, 10)->andReturnNull();
        $repo->shouldNotReceive('update');
        $metaProgressService->shouldNotReceive('recalculateMeta');

        $useCase = new UpdateLancamentoUseCase($repo, $categoriaRepo, $contaRepo, $metaProgressService);
        $result = $useCase->execute(10, 99, []);

        $this->assertTrue($result->isError());
        $this->assertSame(404, $result->httpCode);
        $this->assertSame('Lançamento não encontrado.', $result->message);
    }

    public function testExecuteReturns422WhenLancamentoIsTransferencia(): void
    {
        $repo = Mockery::mock(LancamentoRepository::class);
        $categoriaRepo = Mockery::mock(CategoriaRepository::class);
        $contaRepo = Mockery::mock(ContaRepository::class);
        $metaProgressService = Mockery::mock(MetaProgressService::class);

        $lancamento = new Lancamento();
        $lancamento->id = 55;
        $lancamento->eh_transferencia = 1;

        $repo->shouldReceive('findByIdAndUser')->once()->with(55, 10)->andReturn($lancamento);
        $repo->shouldNotReceive('update');
        $metaProgressService->shouldNotReceive('recalculateMeta');

        $useCase = new UpdateLancamentoUseCase($repo, $categoriaRepo, $contaRepo, $metaProgressService);
        $result = $useCase->execute(10, 55, []);

        $this->assertTrue($result->isError());
        $this->assertSame(422, $result->httpCode);
        $this->assertSame('Transferências não podem ser editadas aqui.', $result->message);
    }

    public function testExecuteReturnsSuccessWithUpdatedId(): void
    {
        $repo = Mockery::mock(LancamentoRepository::class);
        $categoriaRepo = Mockery::mock(CategoriaRepository::class);
        $contaRepo = Mockery::mock(ContaRepository::class);
        $metaProgressService = Mockery::mock(MetaProgressService::class);

        $lancamento = new Lancamento();
        $lancamento->id = 12;
        $lancamento->eh_transferencia = 0;
        $lancamento->tipo = 'despesa';
        $lancamento->data = '2026-03-31';
        $lancamento->valor = 80.50;
        $lancamento->descricao = 'Mercado';
        $lancamento->observacao = 'Semanal';
        $lancamento->forma_pagamento = 'pix';
        $lancamento->conta_id = 7;
        $lancamento->categoria_id = null;
        $lancamento->meta_id = null;
        $lancamento->meta_operacao = null;
        $lancamento->meta_valor = null;

        $repo->shouldReceive('findByIdAndUser')->once()->with(12, 10)->andReturn($lancamento);
        $contaRepo->shouldReceive('belongsToUser')->once()->with(7, 10)->andReturnTrue();
        $repo->shouldReceive('update')->once()->with(12, Mockery::type('array'))->andReturnTrue();
        $metaProgressService->shouldNotReceive('recalculateMeta');

        $useCase = new UpdateLancamentoUseCase($repo, $categoriaRepo, $contaRepo, $metaProgressService);
        $result = $useCase->execute(10, 12, [
            'data' => '2026-03-31',
            'descricao' => 'Mercado atacado',
            'valor' => '99,90',
        ]);

        $this->assertFalse($result->isError());
        $this->assertSame(200, $result->httpCode);
        $this->assertSame('Sucesso', $result->message);
        $this->assertSame(12, $result->data['id'] ?? null);
    }

    public function testExecuteKeepsExistingDecimalStringValorWhenPayloadOmitsValor(): void
    {
        $repo = Mockery::mock(LancamentoRepository::class);
        $categoriaRepo = Mockery::mock(CategoriaRepository::class);
        $contaRepo = Mockery::mock(ContaRepository::class);
        $metaProgressService = Mockery::mock(MetaProgressService::class);

        $lancamento = new Lancamento();
        $lancamento->id = 12;
        $lancamento->eh_transferencia = 0;
        $lancamento->tipo = 'despesa';
        $lancamento->data = '2026-03-31';
        $lancamento->valor = '99.90';
        $lancamento->descricao = 'Internet';
        $lancamento->observacao = 'Mensal';
        $lancamento->forma_pagamento = 'pix';
        $lancamento->conta_id = 7;
        $lancamento->categoria_id = null;
        $lancamento->meta_id = null;
        $lancamento->meta_operacao = null;
        $lancamento->meta_valor = null;

        $repo->shouldReceive('findByIdAndUser')->once()->with(12, 10)->andReturn($lancamento);
        $contaRepo->shouldReceive('belongsToUser')->once()->with(7, 10)->andReturnTrue();
        $repo->shouldReceive('update')->once()->with(12, Mockery::on(
            static fn(array $data): bool => abs((float) ($data['valor'] ?? 0) - 99.90) < 0.0001
                && ($data['descricao'] ?? null) === 'Internet fixa'
        ))->andReturnTrue();
        $metaProgressService->shouldNotReceive('recalculateMeta');

        $useCase = new UpdateLancamentoUseCase($repo, $categoriaRepo, $contaRepo, $metaProgressService);
        $result = $useCase->execute(10, 12, [
            'data' => '2026-03-31',
            'descricao' => 'Internet fixa',
        ]);

        $this->assertFalse($result->isError());
        $this->assertSame(200, $result->httpCode);
        $this->assertSame('Sucesso', $result->message);
        $this->assertSame(12, $result->data['id'] ?? null);
    }
}
