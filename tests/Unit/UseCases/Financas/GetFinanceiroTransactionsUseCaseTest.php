<?php

declare(strict_types=1);

namespace Tests\Unit\UseCases\Financas;

use Application\Models\Categoria;
use Application\Models\Lancamento;
use Application\Repositories\LancamentoRepository;
use Application\UseCases\Financas\GetFinanceiroTransactionsUseCase;
use Illuminate\Database\Eloquent\Collection;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class GetFinanceiroTransactionsUseCaseTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testExecuteReturnsMappedTransactionsPayload(): void
    {
        $categoria = new Categoria();
        $categoria->id = 5;
        $categoria->nome = 'Alimentacao';

        $lancamento = new Lancamento();
        $lancamento->id = 12;
        $lancamento->data = '2026-03-31';
        $lancamento->tipo = 'despesa';
        $lancamento->descricao = 'Mercado';
        $lancamento->observacao = 'Semanal';
        $lancamento->valor = 99.90;
        $lancamento->eh_transferencia = 0;
        $lancamento->eh_saldo_inicial = 0;
        $lancamento->setRelation('categoria', $categoria);

        $repo = Mockery::mock(LancamentoRepository::class);
        $repo->shouldReceive('findTransactionsForPeriod')
            ->once()
            ->with(10, '2026-03-01', '2026-03-31', 50)
            ->andReturn(new Collection([$lancamento]));

        $useCase = new GetFinanceiroTransactionsUseCase($repo);
        $result = $useCase->execute(10, '2026-03-01', '2026-03-31', 50);

        $this->assertFalse($result->isError());
        $this->assertSame(200, $result->httpCode);
        $this->assertCount(1, $result->data);
        $this->assertSame(12, $result->data[0]['id'] ?? null);
        $this->assertSame('Mercado', $result->data[0]['descricao'] ?? null);
        $this->assertSame('Semanal', $result->data[0]['observacao'] ?? null);
        $this->assertSame(['id' => 5, 'nome' => 'Alimentacao'], $result->data[0]['categoria'] ?? null);
    }

    public function testExecuteReturnsEmptyListWhenRepositoryReturnsNoRows(): void
    {
        $repo = Mockery::mock(LancamentoRepository::class);
        $repo->shouldReceive('findTransactionsForPeriod')
            ->once()
            ->with(10, '2026-03-01', '2026-03-31', 10)
            ->andReturn(new Collection());

        $useCase = new GetFinanceiroTransactionsUseCase($repo);
        $result = $useCase->execute(10, '2026-03-01', '2026-03-31', 10);

        $this->assertFalse($result->isError());
        $this->assertSame([], $result->data);
    }
}
