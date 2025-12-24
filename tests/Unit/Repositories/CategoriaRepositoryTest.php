<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use Application\Enums\CategoriaTipo;
use Application\Repositories\CategoriaRepository;
use Tests\TestCase;

/**
 * Testes para CategoriaRepository.
 */
class CategoriaRepositoryTest extends TestCase
{
    private CategoriaRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new CategoriaRepository();
    }

    /** @test */
    public function pode_criar_categoria(): void
    {
        $user = $this->createUser();

        $categoria = $this->repository->create([
            'user_id' => $user->id,
            'nome' => 'Alimentação',
            'tipo' => 'despesa',
        ]);

        $this->assertNotNull($categoria);
        $this->assertEquals('Alimentação', $categoria->nome);
        $this->assertEquals('despesa', $categoria->tipo);
    }

    /** @test */
    public function pode_buscar_categorias_por_usuario_incluindo_globais(): void
    {
        $user = $this->createUser();

        $this->createCategoria($user->id, ['nome' => 'Própria 1']);
        $this->createCategoria($user->id, ['nome' => 'Própria 2']);
        $this->createCategoria(null, ['nome' => 'Global 1']);

        $categorias = $this->repository->findByUser($user->id);

        $this->assertCount(3, $categorias);
    }

    /** @test */
    public function pode_buscar_apenas_categorias_proprias(): void
    {
        $user = $this->createUser();

        $this->createCategoria($user->id, ['nome' => 'Própria 1']);
        $this->createCategoria($user->id, ['nome' => 'Própria 2']);
        $this->createCategoria(null, ['nome' => 'Global 1']);

        $categorias = $this->repository->findOwnByUser($user->id);

        $this->assertCount(2, $categorias);
    }

    /** @test */
    public function pode_buscar_apenas_categorias_globais(): void
    {
        $user = $this->createUser();

        $this->createCategoria($user->id, ['nome' => 'Própria']);
        $this->createCategoria(null, ['nome' => 'Global 1']);
        $this->createCategoria(null, ['nome' => 'Global 2']);

        $categorias = $this->repository->findGlobal();

        $this->assertCount(2, $categorias);
        foreach ($categorias as $categoria) {
            $this->assertNull($categoria->user_id);
        }
    }

    /** @test */
    public function pode_buscar_categorias_por_tipo(): void
    {
        $user = $this->createUser();

        $this->createCategoria($user->id, ['tipo' => 'receita']);
        $this->createCategoria($user->id, ['tipo' => 'receita']);
        $this->createCategoria($user->id, ['tipo' => 'despesa']);

        $categorias = $this->repository->findByType($user->id, CategoriaTipo::RECEITA);

        $this->assertCount(2, $categorias);
    }

    /** @test */
    public function findReceitas_inclui_tipo_ambas(): void
    {
        $user = $this->createUser();

        $this->createCategoria($user->id, ['nome' => 'Receita 1', 'tipo' => 'receita']);
        $this->createCategoria($user->id, ['nome' => 'Ambas', 'tipo' => 'ambas']);
        $this->createCategoria($user->id, ['nome' => 'Despesa', 'tipo' => 'despesa']);

        $categorias = $this->repository->findReceitas($user->id);

        $this->assertCount(2, $categorias);
    }

    /** @test */
    public function findDespesas_inclui_tipo_ambas(): void
    {
        $user = $this->createUser();

        $this->createCategoria($user->id, ['nome' => 'Despesa 1', 'tipo' => 'despesa']);
        $this->createCategoria($user->id, ['nome' => 'Ambas', 'tipo' => 'ambas']);
        $this->createCategoria($user->id, ['nome' => 'Receita', 'tipo' => 'receita']);

        $categorias = $this->repository->findDespesas($user->id);

        $this->assertCount(2, $categorias);
    }

    /** @test */
    public function pode_criar_categoria_para_usuario_com_createForUser(): void
    {
        $user = $this->createUser();

        $categoria = $this->repository->createForUser($user->id, [
            'nome' => 'Nova Categoria',
            'tipo' => 'receita',
        ]);

        $this->assertEquals($user->id, $categoria->user_id);
        $this->assertEquals('Nova Categoria', $categoria->nome);
    }

    /** @test */
    public function pode_atualizar_categoria_com_updateForUser(): void
    {
        $user = $this->createUser();
        $categoria = $this->createCategoria($user->id, ['nome' => 'Nome Antigo']);

        $this->repository->updateForUser($categoria->id, $user->id, [
            'nome' => 'Nome Novo'
        ]);

        $updated = $this->repository->find($categoria->id);
        $this->assertEquals('Nome Novo', $updated->nome);
    }

    /** @test */
    public function pode_deletar_categoria_com_deleteForUser(): void
    {
        $user = $this->createUser();
        $categoria = $this->createCategoria($user->id);

        $this->repository->deleteForUser($categoria->id, $user->id);

        $deleted = $this->repository->find($categoria->id);
        $this->assertNull($deleted);
    }

    /** @test */
    public function belongsToUser_retorna_true_para_categoria_propria(): void
    {
        $user = $this->createUser();
        $categoria = $this->createCategoria($user->id);

        $belongs = $this->repository->belongsToUser($categoria->id, $user->id);

        $this->assertTrue($belongs);
    }

    /** @test */
    public function belongsToUser_retorna_true_para_categoria_global(): void
    {
        $user = $this->createUser();
        $categoriaGlobal = $this->createCategoria(null);

        $belongs = $this->repository->belongsToUser($categoriaGlobal->id, $user->id);

        $this->assertTrue($belongs);
    }

    /** @test */
    public function belongsToUser_retorna_false_para_categoria_de_outro_usuario(): void
    {
        $user1 = $this->createUser(['email' => 'user1@test.com']);
        $user2 = $this->createUser(['email' => 'user2@test.com']);
        $categoria = $this->createCategoria($user1->id);

        $belongs = $this->repository->belongsToUser($categoria->id, $user2->id);

        $this->assertFalse($belongs);
    }

    /** @test */
    public function isGlobal_retorna_true_para_categoria_global(): void
    {
        $categoriaGlobal = $this->createCategoria(null);

        $isGlobal = $this->repository->isGlobal($categoriaGlobal->id);

        $this->assertTrue($isGlobal);
    }

    /** @test */
    public function isGlobal_retorna_false_para_categoria_de_usuario(): void
    {
        $user = $this->createUser();
        $categoria = $this->createCategoria($user->id);

        $isGlobal = $this->repository->isGlobal($categoria->id);

        $this->assertFalse($isGlobal);
    }

    /** @test */
    public function hasDuplicate_retorna_true_se_existe_duplicado(): void
    {
        $user = $this->createUser();
        $this->createCategoria($user->id, [
            'nome' => 'Alimentação',
            'tipo' => 'despesa'
        ]);

        $hasDuplicate = $this->repository->hasDuplicate(
            $user->id,
            'Alimentação',
            'despesa'
        );

        $this->assertTrue($hasDuplicate);
    }

    /** @test */
    public function hasDuplicate_retorna_false_se_nao_existe_duplicado(): void
    {
        $user = $this->createUser();
        $this->createCategoria($user->id, [
            'nome' => 'Alimentação',
            'tipo' => 'despesa'
        ]);

        $hasDuplicate = $this->repository->hasDuplicate(
            $user->id,
            'Transporte',
            'despesa'
        );

        $this->assertFalse($hasDuplicate);
    }

    /** @test */
    public function hasDuplicate_ignora_categoria_sendo_editada(): void
    {
        $user = $this->createUser();
        $categoria = $this->createCategoria($user->id, [
            'nome' => 'Alimentação',
            'tipo' => 'despesa'
        ]);

        // Verificar duplicado ignorando a própria categoria
        $hasDuplicate = $this->repository->hasDuplicate(
            $user->id,
            'Alimentação',
            'despesa',
            $categoria->id
        );

        $this->assertFalse($hasDuplicate);
    }

    /** @test */
    public function pode_buscar_categorias_mais_usadas(): void
    {
        $user = $this->createUser();
        
        $cat1 = $this->createCategoria($user->id, ['nome' => 'Categoria 1']);
        $cat2 = $this->createCategoria($user->id, ['nome' => 'Categoria 2']);
        $cat3 = $this->createCategoria($user->id, ['nome' => 'Categoria 3']);

        // Cat1: 5 lançamentos
        for ($i = 0; $i < 5; $i++) {
            $this->createLancamento($user->id, ['categoria_id' => $cat1->id]);
        }
        
        // Cat2: 3 lançamentos
        for ($i = 0; $i < 3; $i++) {
            $this->createLancamento($user->id, ['categoria_id' => $cat2->id]);
        }
        
        // Cat3: 1 lançamento
        $this->createLancamento($user->id, ['categoria_id' => $cat3->id]);

        $topCategorias = $this->repository->findMostUsed($user->id, 2);

        $this->assertCount(2, $topCategorias);
        $this->assertEquals($cat1->id, $topCategorias->first()->id);
    }

    /** @test */
    public function pode_buscar_categorias_nao_usadas(): void
    {
        $user = $this->createUser();
        
        $catUsada = $this->createCategoria($user->id, ['nome' => 'Usada']);
        $catNaoUsada = $this->createCategoria($user->id, ['nome' => 'Não Usada']);

        $this->createLancamento($user->id, ['categoria_id' => $catUsada->id]);

        $naoUsadas = $this->repository->findUnused($user->id);

        $this->assertCount(1, $naoUsadas);
        $this->assertEquals($catNaoUsada->id, $naoUsadas->first()->id);
    }

    /** @test */
    public function pode_contar_categorias_por_tipo(): void
    {
        $user = $this->createUser();

        $this->createCategoria($user->id, ['tipo' => 'receita']);
        $this->createCategoria($user->id, ['tipo' => 'receita']);
        $this->createCategoria($user->id, ['tipo' => 'despesa']);

        $count = $this->repository->countByType($user->id, CategoriaTipo::RECEITA);

        $this->assertEquals(2, $count);
    }

    /** @test */
    public function findByIdAndUser_retorna_categoria_propria(): void
    {
        $user = $this->createUser();
        $categoria = $this->createCategoria($user->id);

        $found = $this->repository->findByIdAndUser($categoria->id, $user->id);

        $this->assertNotNull($found);
        $this->assertEquals($categoria->id, $found->id);
    }

    /** @test */
    public function findByIdAndUser_retorna_categoria_global(): void
    {
        $user = $this->createUser();
        $categoriaGlobal = $this->createCategoria(null);

        $found = $this->repository->findByIdAndUser($categoriaGlobal->id, $user->id);

        $this->assertNotNull($found);
    }

    /** @test */
    public function findByIdAndUser_retorna_null_para_categoria_de_outro_usuario(): void
    {
        $user1 = $this->createUser(['email' => 'user1@test.com']);
        $user2 = $this->createUser(['email' => 'user2@test.com']);
        $categoria = $this->createCategoria($user1->id);

        $found = $this->repository->findByIdAndUser($categoria->id, $user2->id);

        $this->assertNull($found);
    }

    /** @test */
    public function findOwnByIdAndUser_retorna_apenas_categoria_propria(): void
    {
        $user = $this->createUser();
        $categoria = $this->createCategoria($user->id);
        $categoriaGlobal = $this->createCategoria(null);

        $foundPropria = $this->repository->findOwnByIdAndUser($categoria->id, $user->id);
        $foundGlobal = $this->repository->findOwnByIdAndUser($categoriaGlobal->id, $user->id);

        $this->assertNotNull($foundPropria);
        $this->assertNull($foundGlobal);
    }
}
