<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use Application\Enums\LancamentoTipo;
use Application\Repositories\LancamentoRepository;
use Tests\TestCase;

/**
 * Testes para LancamentoRepository.
 */
class LancamentoRepositoryTest extends TestCase
{
    private LancamentoRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new LancamentoRepository();
    }

    /** @test */
    public function pode_criar_lancamento(): void
    {
        $user = $this->createUser();
        $conta = $this->createConta($user->id);

        $lancamento = $this->repository->create([
            'user_id' => $user->id,
            'data' => '2025-12-19',
            'tipo' => 'receita',
            'valor' => 150.00,
            'descricao' => 'Salário',
            'conta_id' => $conta->id,
            'eh_transferencia' => 0,
            'eh_saldo_inicial' => 0,
        ]);

        $this->assertNotNull($lancamento);
        $this->assertEquals(150.00, $lancamento->valor);
        $this->assertEquals('receita', $lancamento->tipo);
    }

    /** @test */
    public function pode_buscar_lancamento_por_id(): void
    {
        $user = $this->createUser();
        $lancamento = $this->createLancamento($user->id);

        $found = $this->repository->find($lancamento->id);

        $this->assertNotNull($found);
        $this->assertEquals($lancamento->id, $found->id);
    }

    /** @test */
    public function pode_buscar_lancamentos_por_usuario(): void
    {
        $user1 = $this->createUser(['email' => 'user1@test.com']);
        $user2 = $this->createUser(['email' => 'user2@test.com']);

        $this->createLancamento($user1->id, ['descricao' => 'Lançamento 1']);
        $this->createLancamento($user1->id, ['descricao' => 'Lançamento 2']);
        $this->createLancamento($user2->id, ['descricao' => 'Lançamento 3']);

        $lancamentos = $this->repository->findByUser($user1->id);

        $this->assertCount(2, $lancamentos);
    }

    /** @test */
    public function pode_buscar_lancamentos_por_mes(): void
    {
        $user = $this->createUser();

        $this->createLancamento($user->id, ['data' => '2025-12-10']);
        $this->createLancamento($user->id, ['data' => '2025-12-20']);
        $this->createLancamento($user->id, ['data' => '2025-11-15']);

        $lancamentos = $this->repository->findByUserAndMonth($user->id, '2025-12');

        $this->assertCount(2, $lancamentos);
    }

    /** @test */
    public function pode_buscar_lancamentos_por_periodo(): void
    {
        $user = $this->createUser();

        $this->createLancamento($user->id, ['data' => '2025-12-01']);
        $this->createLancamento($user->id, ['data' => '2025-12-15']);
        $this->createLancamento($user->id, ['data' => '2025-12-31']);

        $lancamentos = $this->repository->findByPeriod($user->id, '2025-12-10', '2025-12-20');

        $this->assertCount(1, $lancamentos);
        $data = $lancamentos->first()->data;
        if ($data instanceof \Illuminate\Support\Carbon) {
            $data = $data->format('Y-m-d');
        }
        $this->assertEquals('2025-12-15', $data);
    }

    /** @test */
    public function pode_buscar_lancamentos_por_conta(): void
    {
        $user = $this->createUser();
        $conta1 = $this->createConta($user->id, ['nome' => 'Conta 1']);
        $conta2 = $this->createConta($user->id, ['nome' => 'Conta 2']);

        $this->createLancamento($user->id, ['conta_id' => $conta1->id]);
        $this->createLancamento($user->id, ['conta_id' => $conta1->id]);
        $this->createLancamento($user->id, ['conta_id' => $conta2->id]);

        $lancamentos = $this->repository->findByAccount($user->id, $conta1->id);

        $this->assertCount(2, $lancamentos);
    }

    /** @test */
    public function pode_buscar_lancamentos_por_categoria(): void
    {
        $user = $this->createUser();
        $categoria1 = $this->createCategoria($user->id, ['nome' => 'Alimentação']);
        $categoria2 = $this->createCategoria($user->id, ['nome' => 'Transporte']);

        $this->createLancamento($user->id, ['categoria_id' => $categoria1->id]);
        $this->createLancamento($user->id, ['categoria_id' => $categoria1->id]);
        $this->createLancamento($user->id, ['categoria_id' => $categoria2->id]);

        $lancamentos = $this->repository->findByCategory($user->id, $categoria1->id);

        $this->assertCount(2, $lancamentos);
    }

    /** @test */
    public function pode_buscar_apenas_receitas(): void
    {
        $user = $this->createUser();

        $this->createLancamento($user->id, ['tipo' => 'receita']);
        $this->createLancamento($user->id, ['tipo' => 'receita']);
        $this->createLancamento($user->id, ['tipo' => 'despesa']);

        $receitas = $this->repository->findReceitas($user->id);

        $this->assertCount(2, $receitas);
        foreach ($receitas as $receita) {
            $this->assertEquals('receita', $receita->tipo);
        }
    }

    /** @test */
    public function pode_buscar_apenas_despesas(): void
    {
        $user = $this->createUser();

        $this->createLancamento($user->id, ['tipo' => 'receita']);
        $this->createLancamento($user->id, ['tipo' => 'despesa']);
        $this->createLancamento($user->id, ['tipo' => 'despesa']);

        $despesas = $this->repository->findDespesas($user->id);

        $this->assertCount(2, $despesas);
        foreach ($despesas as $despesa) {
            $this->assertEquals('despesa', $despesa->tipo);
        }
    }

    /** @test */
    public function pode_buscar_apenas_transferencias(): void
    {
        $user = $this->createUser();

        $this->createLancamento($user->id, ['eh_transferencia' => 1]);
        $this->createLancamento($user->id, ['eh_transferencia' => 1]);
        $this->createLancamento($user->id, ['eh_transferencia' => 0]);

        $transferencias = $this->repository->findTransferencias($user->id);

        $this->assertCount(2, $transferencias);
    }

    /** @test */
    public function pode_contar_lancamentos_por_mes(): void
    {
        $user = $this->createUser();

        // Cria 3 lançamentos normais em dezembro
        $this->createLancamento($user->id, ['data' => '2025-12-10']);
        $this->createLancamento($user->id, ['data' => '2025-12-20']);
        
        // Cria 1 transferência (não deve contar)
        $this->createLancamento($user->id, [
            'data' => '2025-12-15',
            'eh_transferencia' => 1
        ]);
        
        // Cria 1 saldo inicial (não deve contar)
        $this->createLancamento($user->id, [
            'data' => '2025-12-01',
            'eh_saldo_inicial' => 1
        ]);

        $count = $this->repository->countByMonth($user->id, '2025-12');

        $this->assertEquals(2, $count);
    }

    /** @test */
    public function pode_somar_valor_por_tipo_e_periodo(): void
    {
        $user = $this->createUser();

        $this->createLancamento($user->id, [
            'tipo' => 'receita',
            'valor' => 100.00,
            'data' => '2025-12-10'
        ]);
        $this->createLancamento($user->id, [
            'tipo' => 'receita',
            'valor' => 200.00,
            'data' => '2025-12-20'
        ]);
        $this->createLancamento($user->id, [
            'tipo' => 'despesa',
            'valor' => 50.00,
            'data' => '2025-12-15'
        ]);

        $total = $this->repository->sumByTypeAndPeriod(
            $user->id,
            '2025-12-01',
            '2025-12-31',
            LancamentoTipo::RECEITA
        );

        $this->assertEquals(300.00, $total);
    }

    /** @test */
    public function pode_atualizar_lancamento(): void
    {
        $user = $this->createUser();
        $lancamento = $this->createLancamento($user->id, ['valor' => 100.00]);

        $this->repository->update($lancamento->id, ['valor' => 250.00]);

        $updated = $this->repository->find($lancamento->id);
        $this->assertEquals(250.00, $updated->valor);
    }

    /** @test */
    public function pode_deletar_lancamento(): void
    {
        $user = $this->createUser();
        $lancamento = $this->createLancamento($user->id);

        $this->repository->delete($lancamento->id);

        $found = $this->repository->find($lancamento->id);
        $this->assertNull($found);
    }

    /** @test */
    public function pode_deletar_lancamentos_por_conta(): void
    {
        $user = $this->createUser();
        $conta = $this->createConta($user->id);

        $this->createLancamento($user->id, ['conta_id' => $conta->id]);
        $this->createLancamento($user->id, ['conta_id' => $conta->id]);
        $this->createLancamento($user->id, ['conta_id' => $conta->id]);

        $deleted = $this->repository->deleteByAccount($user->id, $conta->id);

        $this->assertEquals(3, $deleted);
        $remaining = $this->repository->findByAccount($user->id, $conta->id);
        $this->assertCount(0, $remaining);
    }

    /** @test */
    public function pode_atualizar_categoria_em_massa(): void
    {
        $user = $this->createUser();
        $categoriaAntiga = $this->createCategoria($user->id, ['nome' => 'Antiga']);
        $categoriaNova = $this->createCategoria($user->id, ['nome' => 'Nova']);

        $this->createLancamento($user->id, ['categoria_id' => $categoriaAntiga->id]);
        $this->createLancamento($user->id, ['categoria_id' => $categoriaAntiga->id]);

        $updated = $this->repository->updateCategory(
            $user->id,
            $categoriaAntiga->id,
            $categoriaNova->id
        );

        $this->assertEquals(2, $updated);
        
        $lancamentos = $this->repository->findByCategory($user->id, $categoriaNova->id);
        $this->assertCount(2, $lancamentos);
    }

    /** @test */
    public function findByIdAndUser_retorna_null_se_nao_pertence_ao_usuario(): void
    {
        $user1 = $this->createUser(['email' => 'user1@test.com']);
        $user2 = $this->createUser(['email' => 'user2@test.com']);
        
        $lancamento = $this->createLancamento($user1->id);

        $found = $this->repository->findByIdAndUser($lancamento->id, $user2->id);

        $this->assertNull($found);
    }

    /** @test */
    public function findByIdAndUser_retorna_lancamento_se_pertence_ao_usuario(): void
    {
        $user = $this->createUser();
        $lancamento = $this->createLancamento($user->id);

        $found = $this->repository->findByIdAndUser($lancamento->id, $user->id);

        $this->assertNotNull($found);
        $this->assertEquals($lancamento->id, $found->id);
    }
}
