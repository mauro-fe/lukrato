<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Database\Capsule\Manager as Capsule;
use PHPUnit\Framework\TestCase as BaseTestCase;

/**
 * Classe base para todos os testes.
 */
abstract class TestCase extends BaseTestCase
{
    /**
     * Setup executado antes de cada teste.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Limpa todas as tabelas antes de cada teste
        $this->truncateTables();
    }

    /**
     * Limpa todas as tabelas do banco.
     */
    protected function truncateTables(): void
    {
        Capsule::table('assinaturas_usuarios')->truncate();
        Capsule::table('lancamentos')->truncate();
        Capsule::table('categorias')->truncate();
        Capsule::table('contas')->truncate();
        Capsule::table('usuarios')->truncate();
        Capsule::table('planos')->truncate();
    }

    /**
     * Cria um usuário de teste.
     */
    protected function createUser(array $attributes = []): object
    {
        $defaults = [
            'nome' => 'Usuario Teste',
            'email' => 'teste@example.com',
            'senha' => password_hash('senha123', PASSWORD_DEFAULT),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $data = array_merge($defaults, $attributes);
        $id = Capsule::table('usuarios')->insertGetId($data);

        return (object) array_merge($data, ['id' => $id]);
    }

    /**
     * Cria uma conta de teste.
     */
    protected function createConta(int $userId, array $attributes = []): object
    {
        $defaults = [
            'user_id' => $userId,
            'nome' => 'Conta Teste',
            'instituicao' => 'Banco Teste',
            'moeda' => 'BRL',
            'saldo_inicial' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $data = array_merge($defaults, $attributes);
        $id = Capsule::table('contas')->insertGetId($data);

        return (object) array_merge($data, ['id' => $id]);
    }

    /**
     * Cria uma categoria de teste.
     */
    protected function createCategoria(?int $userId, array $attributes = []): object
    {
        $defaults = [
            'user_id' => $userId,
            'nome' => 'Categoria Teste',
            'tipo' => 'receita',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $data = array_merge($defaults, $attributes);
        $id = Capsule::table('categorias')->insertGetId($data);

        return (object) array_merge($data, ['id' => $id]);
    }

    /**
     * Cria um lançamento de teste.
     */
    protected function createLancamento(int $userId, array $attributes = []): object
    {
        $defaults = [
            'user_id' => $userId,
            'data' => date('Y-m-d'),
            'tipo' => 'receita',
            'valor' => 100.00,
            'descricao' => 'Lançamento Teste',
            'eh_transferencia' => 0,
            'eh_saldo_inicial' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $data = array_merge($defaults, $attributes);
        $id = Capsule::table('lancamentos')->insertGetId($data);

        return (object) array_merge($data, ['id' => $id]);
    }

    /**
     * Cria um plano de teste.
     */
    protected function createPlano(array $attributes = []): object
    {
        $defaults = [
            'code' => 'free',
            'name' => 'Plano Free',
            'price' => 0.00,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $data = array_merge($defaults, $attributes);
        $id = Capsule::table('planos')->insertGetId($data);

        return (object) array_merge($data, ['id' => $id]);
    }

    /**
     * Cria uma assinatura de teste.
     */
    protected function createAssinatura(int $userId, int $planoId, array $attributes = []): object
    {
        $defaults = [
            'user_id' => $userId,
            'plano_id' => $planoId,
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $data = array_merge($defaults, $attributes);
        $id = Capsule::table('assinaturas_usuarios')->insertGetId($data);

        return (object) array_merge($data, ['id' => $id]);
    }
}
