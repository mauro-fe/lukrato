<?php

declare(strict_types=1);

namespace Tests\Unit\UseCases\Financas;

use Application\Models\Categoria;
use Application\Models\Conta;
use Application\Repositories\CategoriaRepository;
use Application\Repositories\ContaRepository;
use Application\UseCases\Financas\GetFinanceiroOptionsUseCase;
use Illuminate\Database\Eloquent\Collection;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class GetFinanceiroOptionsUseCaseTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testExecuteReturnsMappedCategoriasAndContas(): void
    {
        $receita = new Categoria();
        $receita->id = 1;
        $receita->nome = 'Salario';

        $despesa = new Categoria();
        $despesa->id = 2;
        $despesa->nome = 'Mercado';

        $conta = new Conta();
        $conta->id = 8;
        $conta->nome = 'Conta Principal';

        $categoriaRepo = Mockery::mock(CategoriaRepository::class);
        $contaRepo = Mockery::mock(ContaRepository::class);

        $categoriaRepo->shouldReceive('findReceitas')->once()->with(10)->andReturn(new Collection([$receita]));
        $categoriaRepo->shouldReceive('findDespesas')->once()->with(10)->andReturn(new Collection([$despesa]));
        $contaRepo->shouldReceive('findActive')->once()->with(10)->andReturn(new Collection([$conta]));

        $useCase = new GetFinanceiroOptionsUseCase($categoriaRepo, $contaRepo);
        $result = $useCase->execute(10);

        $this->assertFalse($result->isError());
        $this->assertSame(200, $result->httpCode);
        $this->assertSame([['id' => 1, 'nome' => 'Salario']], $result->data['categorias']['receitas'] ?? null);
        $this->assertSame([['id' => 2, 'nome' => 'Mercado']], $result->data['categorias']['despesas'] ?? null);
        $this->assertSame([['id' => 8, 'nome' => 'Conta Principal']], $result->data['contas'] ?? null);
    }

    public function testExecuteReturnsEmptyListsWhenNoDataIsAvailable(): void
    {
        $categoriaRepo = Mockery::mock(CategoriaRepository::class);
        $contaRepo = Mockery::mock(ContaRepository::class);

        $categoriaRepo->shouldReceive('findReceitas')->once()->with(10)->andReturn(new Collection());
        $categoriaRepo->shouldReceive('findDespesas')->once()->with(10)->andReturn(new Collection());
        $contaRepo->shouldReceive('findActive')->once()->with(10)->andReturn(new Collection());

        $useCase = new GetFinanceiroOptionsUseCase($categoriaRepo, $contaRepo);
        $result = $useCase->execute(10);

        $this->assertFalse($result->isError());
        $this->assertSame([], $result->data['categorias']['receitas'] ?? null);
        $this->assertSame([], $result->data['categorias']['despesas'] ?? null);
        $this->assertSame([], $result->data['contas'] ?? null);
    }
}
