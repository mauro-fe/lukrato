<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use Application\Models\Conta;
use Application\Repositories\ContaRepository;
use Tests\TestCase;

/**
 * Testes para ContaRepository.
 */
class ContaRepositoryTest extends TestCase
{
    private ContaRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new ContaRepository();
    }

    /** @test */
    public function pode_criar_conta(): void
    {
        $user = $this->createUser();

        $conta = $this->repository->create([
            'user_id' => $user->id,
            'nome' => 'Conta Corrente',
            'instituicao' => 'Banco do Brasil',
            'moeda' => 'BRL',
            'saldo_inicial' => 1000.00,
        ]);

        $this->assertNotNull($conta);
        $this->assertEquals('Conta Corrente', $conta->nome);
        $this->assertEquals('BRL', $conta->moeda);
    }

    /** @test */
    public function pode_buscar_contas_por_usuario(): void
    {
        $user1 = $this->createUser(['email' => 'user1@test.com']);
        $user2 = $this->createUser(['email' => 'user2@test.com']);

        $this->createConta($user1->id, ['nome' => 'Conta 1']);
        $this->createConta($user1->id, ['nome' => 'Conta 2']);
        $this->createConta($user2->id, ['nome' => 'Conta 3']);

        $contas = $this->repository->findByUser($user1->id);

        $this->assertCount(2, $contas);
    }

    /** @test */
    public function pode_buscar_apenas_contas_ativas(): void
    {
        $user = $this->createUser();

        $this->createConta($user->id, ['nome' => 'Ativa 1']);
        $this->createConta($user->id, ['nome' => 'Ativa 2']);
        $this->createConta($user->id, [
            'nome' => 'Arquivada',
            'deleted_at' => date('Y-m-d H:i:s')
        ]);

        $contas = $this->repository->findActive($user->id);

        $this->assertCount(2, $contas);
    }

    /** @test */
    public function pode_buscar_apenas_contas_arquivadas(): void
    {
        $user = $this->createUser();

        $this->createConta($user->id, ['nome' => 'Ativa']);
        $this->createConta($user->id, [
            'nome' => 'Arquivada 1',
            'deleted_at' => date('Y-m-d H:i:s')
        ]);
        $this->createConta($user->id, [
            'nome' => 'Arquivada 2',
            'deleted_at' => date('Y-m-d H:i:s')
        ]);

        $contas = $this->repository->findArchived($user->id);

        $this->assertCount(2, $contas);
    }

    /** @test */
    public function pode_buscar_contas_por_moeda(): void
    {
        $user = $this->createUser();

        $this->createConta($user->id, ['moeda' => 'BRL']);
        $this->createConta($user->id, ['moeda' => 'BRL']);
        $this->createConta($user->id, ['moeda' => 'USD']);

        $contas = $this->repository->findByMoeda($user->id, 'BRL');

        $this->assertCount(2, $contas);
    }

    /** @test */
    public function pode_arquivar_conta(): void
    {
        $user = $this->createUser();
        $conta = $this->createConta($user->id);

        $this->repository->archive($conta->id, $user->id);

        $arquivada = Conta::withTrashed()->find($conta->id);
        $this->assertNotNull($arquivada->deleted_at);
    }

    /** @test */
    public function pode_restaurar_conta(): void
    {
        $user = $this->createUser();
        $conta = $this->createConta($user->id, [
            'deleted_at' => date('Y-m-d H:i:s')
        ]);

        $this->repository->restore($conta->id, $user->id);

        $restaurada = $this->repository->find($conta->id);
        $this->assertNull($restaurada->deleted_at);
    }

    /** @test */
    public function pode_criar_conta_para_usuario_com_createForUser(): void
    {
        $user = $this->createUser();

        $conta = $this->repository->createForUser($user->id, [
            'nome' => 'Nova Conta',
            'moeda' => 'USD',
        ]);

        $this->assertEquals($user->id, $conta->user_id);
        $this->assertEquals('Nova Conta', $conta->nome);
    }

    /** @test */
    public function pode_atualizar_conta_com_updateForUser(): void
    {
        $user = $this->createUser();
        $conta = $this->createConta($user->id, ['nome' => 'Nome Antigo']);

        $this->repository->updateForUser($conta->id, $user->id, [
            'nome' => 'Nome Novo'
        ]);

        $updated = $this->repository->find($conta->id);
        $this->assertEquals('Nome Novo', $updated->nome);
    }

    /** @test */
    public function pode_deletar_conta_com_deleteForUser(): void
    {
        $user = $this->createUser();
        $conta = $this->createConta($user->id);

        $this->repository->deleteForUser($conta->id, $user->id);

        $deleted = $this->repository->find($conta->id);
        $this->assertNull($deleted);
    }

    /** @test */
    public function belongsToUser_retorna_true_se_conta_pertence_ao_usuario(): void
    {
        $user = $this->createUser();
        $conta = $this->createConta($user->id);

        $belongs = $this->repository->belongsToUser($conta->id, $user->id);

        $this->assertTrue($belongs);
    }

    /** @test */
    public function belongsToUser_retorna_false_se_conta_nao_pertence_ao_usuario(): void
    {
        $user1 = $this->createUser(['email' => 'user1@test.com']);
        $user2 = $this->createUser(['email' => 'user2@test.com']);
        $conta = $this->createConta($user1->id);

        $belongs = $this->repository->belongsToUser($conta->id, $user2->id);

        $this->assertFalse($belongs);
    }

    /** @test */
    public function hasDuplicateName_retorna_true_se_existe_nome_duplicado(): void
    {
        $user = $this->createUser();
        $this->createConta($user->id, ['nome' => 'Conta Existente']);

        $hasDuplicate = $this->repository->hasDuplicateName($user->id, 'Conta Existente');

        $this->assertTrue($hasDuplicate);
    }

    /** @test */
    public function hasDuplicateName_retorna_false_se_nao_existe_duplicado(): void
    {
        $user = $this->createUser();
        $this->createConta($user->id, ['nome' => 'Conta Existente']);

        $hasDuplicate = $this->repository->hasDuplicateName($user->id, 'Conta Nova');

        $this->assertFalse($hasDuplicate);
    }

    /** @test */
    public function hasDuplicateName_ignora_conta_sendo_editada(): void
    {
        $user = $this->createUser();
        $conta = $this->createConta($user->id, ['nome' => 'Minha Conta']);

        // Verificar se existe duplicado ignorando a própria conta
        $hasDuplicate = $this->repository->hasDuplicateName(
            $user->id,
            'Minha Conta',
            $conta->id
        );

        $this->assertFalse($hasDuplicate);
    }

    /** @test */
    public function pode_contar_contas_ativas(): void
    {
        $user = $this->createUser();

        $this->createConta($user->id);
        $this->createConta($user->id);
        $this->createConta($user->id, ['deleted_at' => date('Y-m-d H:i:s')]);

        $count = $this->repository->countActive($user->id);

        $this->assertEquals(2, $count);
    }

    /** @test */
    public function pode_contar_todas_contas_do_usuario(): void
    {
        $user = $this->createUser();

        $this->createConta($user->id);
        $this->createConta($user->id);
        $this->createConta($user->id, ['deleted_at' => date('Y-m-d H:i:s')]);

        $count = $this->repository->countByUser($user->id);

        $this->assertEquals(3, $count);
    }

    /** @test */
    public function pode_obter_ids_de_contas_ativas(): void
    {
        $user = $this->createUser();

        $conta1 = $this->createConta($user->id);
        $conta2 = $this->createConta($user->id);
        $this->createConta($user->id, ['deleted_at' => date('Y-m-d H:i:s')]);

        $ids = $this->repository->getIdsByUser($user->id, true);

        $this->assertCount(2, $ids);
        $this->assertContains($conta1->id, $ids);
        $this->assertContains($conta2->id, $ids);
    }

    /** @test */
    public function pode_obter_ids_de_todas_contas(): void
    {
        $user = $this->createUser();

        $this->createConta($user->id);
        $this->createConta($user->id);
        $this->createConta($user->id, ['deleted_at' => date('Y-m-d H:i:s')]);

        $ids = $this->repository->getIdsByUser($user->id, false);

        $this->assertCount(3, $ids);
    }

    /** @test */
    public function findByIdAndUser_retorna_null_se_nao_pertence_ao_usuario(): void
    {
        $user1 = $this->createUser(['email' => 'user1@test.com']);
        $user2 = $this->createUser(['email' => 'user2@test.com']);
        $conta = $this->createConta($user1->id);

        $found = $this->repository->findByIdAndUser($conta->id, $user2->id);

        $this->assertNull($found);
    }

    /** @test */
    public function findByIdAndUserOrFail_lanca_excecao_se_nao_encontrar(): void
    {
        $user = $this->createUser();

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->repository->findByIdAndUserOrFail(999, $user->id);
    }
}
