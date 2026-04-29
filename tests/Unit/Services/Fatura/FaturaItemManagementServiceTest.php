<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Fatura;

use Application\Services\Fatura\FaturaItemManagementService;
use Illuminate\Database\Capsule\Manager as DB;
use PHPUnit\Framework\TestCase;

class FaturaItemManagementServiceTest extends TestCase
{
    /**
     * @var array<int, int>
     */
    private array $cleanupUserIds = [];

    protected function tearDown(): void
    {
        if ($this->cleanupUserIds !== []) {
            DB::table('lancamentos')->whereIn('user_id', $this->cleanupUserIds)->delete();
            DB::table('faturas_cartao_itens')->whereIn('user_id', $this->cleanupUserIds)->delete();
            DB::table('faturas')->whereIn('user_id', $this->cleanupUserIds)->delete();
            DB::table('cartoes_credito')->whereIn('user_id', $this->cleanupUserIds)->delete();
            DB::table('contas')->whereIn('user_id', $this->cleanupUserIds)->delete();
            DB::table('categorias')->whereIn('user_id', $this->cleanupUserIds)->delete();
            DB::table('usuarios')->whereIn('id', $this->cleanupUserIds)->delete();
        }

        $this->cleanupUserIds = [];

        parent::tearDown();
    }

    public function testExcluirItemPermiteExcluirEstornoPago(): void
    {
        $this->ensureDatabaseAvailable();

        $service = new FaturaItemManagementService();
        $userId = $this->createUser();
        $contaId = $this->createConta($userId);
        $cartaoId = $this->createCartao($userId, $contaId, 1000);
        $faturaId = $this->createFatura($userId, $cartaoId, 250);

        $this->createFaturaItem($userId, $cartaoId, $faturaId, [
            'descricao' => 'Compra principal',
            'valor' => 300,
            'tipo' => 'despesa',
            'pago' => 0,
            'data_pagamento' => null,
        ]);

        $estornoId = $this->createFaturaItem($userId, $cartaoId, $faturaId, [
            'descricao' => '↩️ Cashback',
            'valor' => -50,
            'tipo' => 'estorno',
            'pago' => 1,
            'data_pagamento' => '2026-04-02',
        ]);

        $result = $service->excluirItem($faturaId, $estornoId, $userId);

        $this->assertTrue($result['success']);
        $this->assertSame('Item excluído com sucesso', $result['message']);
        $this->assertSame(0, DB::table('faturas_cartao_itens')->where('id', $estornoId)->count());
        $this->assertSame(300.0, (float) DB::table('faturas')->where('id', $faturaId)->value('valor_total'));
        $this->assertSame(700.0, (float) DB::table('cartoes_credito')->where('id', $cartaoId)->value('limite_disponivel'));
    }

    public function testExcluirItemContinuaBloqueandoDespesaPaga(): void
    {
        $this->ensureDatabaseAvailable();

        $service = new FaturaItemManagementService();
        $userId = $this->createUser();
        $contaId = $this->createConta($userId);
        $cartaoId = $this->createCartao($userId, $contaId, 1000);
        $faturaId = $this->createFatura($userId, $cartaoId, 120);

        $itemId = $this->createFaturaItem($userId, $cartaoId, $faturaId, [
            'descricao' => 'Compra paga',
            'valor' => 120,
            'tipo' => 'despesa',
            'pago' => 1,
            'data_pagamento' => '2026-04-02',
        ]);

        $result = $service->excluirItem($faturaId, $itemId, $userId);

        $this->assertFalse($result['success']);
        $this->assertSame('Não é possível excluir um item já pago. Desfaça o pagamento primeiro.', $result['message']);
        $this->assertSame(1, DB::table('faturas_cartao_itens')->where('id', $itemId)->count());
    }

    public function testAtualizarItemPermiteDefinirCategoriaESubcategoria(): void
    {
        $this->ensureDatabaseAvailable();

        $service = new FaturaItemManagementService();
        $userId = $this->createUser();
        $contaId = $this->createConta($userId);
        $cartaoId = $this->createCartao($userId, $contaId, 1000);
        $faturaId = $this->createFatura($userId, $cartaoId, 120);
        $categoriaId = $this->createCategoria($userId, 'Transporte');
        $subcategoriaId = $this->createSubcategoria($userId, $categoriaId, 'Aplicativo');
        $itemId = $this->createFaturaItem($userId, $cartaoId, $faturaId, [
            'descricao' => 'Uber',
            'valor' => 120,
        ]);

        $updated = $service->atualizarItem($faturaId, $itemId, $userId, [
            'categoria_id' => $categoriaId,
            'subcategoria_id' => $subcategoriaId,
        ]);

        $this->assertTrue($updated);

        $item = DB::table('faturas_cartao_itens')->where('id', $itemId)->first();
        $this->assertSame($categoriaId, (int) ($item->categoria_id ?? 0));
        $this->assertSame($subcategoriaId, (int) ($item->subcategoria_id ?? 0));
    }

    public function testAtualizarCategoriaSincronizaLancamentoVinculado(): void
    {
        $this->ensureDatabaseAvailable();

        $service = new FaturaItemManagementService();
        $userId = $this->createUser();
        $contaId = $this->createConta($userId);
        $cartaoId = $this->createCartao($userId, $contaId, 1000);
        $faturaId = $this->createFatura($userId, $cartaoId, 120);
        $categoriaId = $this->createCategoria($userId, 'Transporte');
        $subcategoriaId = $this->createSubcategoria($userId, $categoriaId, 'Aplicativo');
        $lancamentoId = $this->createLancamento($userId, $contaId, $cartaoId);
        $itemId = $this->createFaturaItem($userId, $cartaoId, $faturaId, [
            'descricao' => 'Uber pago',
            'valor' => 120,
            'pago' => 1,
            'lancamento_id' => $lancamentoId,
        ]);

        $updated = $service->atualizarItem($faturaId, $itemId, $userId, [
            'categoria_id' => $categoriaId,
            'subcategoria_id' => $subcategoriaId,
        ]);

        $this->assertTrue($updated);

        $lancamento = DB::table('lancamentos')->where('id', $lancamentoId)->first();
        $this->assertSame($categoriaId, (int) ($lancamento->categoria_id ?? 0));
        $this->assertSame($subcategoriaId, (int) ($lancamento->subcategoria_id ?? 0));
    }

    private function ensureDatabaseAvailable(): void
    {
        try {
            DB::connection()->getPdo();
        } catch (\Throwable) {
            $this->markTestSkipped('Database connection required for fatura item management tests');
        }
    }

    private function createUser(): int
    {
        $userId = (int) DB::table('usuarios')->insertGetId([
            'nome' => 'Usuario Fatura',
            'email' => 'fatura-item-' . bin2hex(random_bytes(5)) . '@example.com',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->cleanupUserIds[] = $userId;

        return $userId;
    }

    private function createConta(int $userId): int
    {
        return (int) DB::table('contas')->insertGetId([
            'user_id' => $userId,
            'nome' => 'Conta Fatura',
            'instituicao' => 'Banco Teste',
            'tipo_conta' => 'corrente',
            'saldo_inicial' => 0,
            'ativo' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function createCartao(int $userId, int $contaId, float $limiteTotal): int
    {
        return (int) DB::table('cartoes_credito')->insertGetId([
            'user_id' => $userId,
            'conta_id' => $contaId,
            'nome_cartao' => 'Cartao Fatura',
            'bandeira' => 'visa',
            'ultimos_digitos' => '1234',
            'limite_total' => number_format($limiteTotal, 2, '.', ''),
            'limite_disponivel' => number_format($limiteTotal, 2, '.', ''),
            'dia_vencimento' => 10,
            'dia_fechamento' => 1,
            'cor_cartao' => '#2563eb',
            'ativo' => 1,
            'arquivado' => 0,
            'fatura_canal_email' => 0,
            'fatura_canal_inapp' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function createFatura(int $userId, int $cartaoId, float $valorTotal): int
    {
        return (int) DB::table('faturas')->insertGetId([
            'user_id' => $userId,
            'cartao_credito_id' => $cartaoId,
            'descricao' => 'Fatura 4/2026',
            'valor_total' => number_format($valorTotal, 2, '.', ''),
            'numero_parcelas' => 0,
            'data_compra' => '2026-04-01',
            'status' => 'pendente',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function createCategoria(int $userId, string $nome): int
    {
        return (int) DB::table('categorias')->insertGetId([
            'user_id' => $userId,
            'parent_id' => null,
            'nome' => $nome,
            'icone' => 'tag',
            'tipo' => 'despesa',
            'is_seeded' => 0,
            'ordem' => 0,
        ]);
    }

    private function createSubcategoria(int $userId, int $categoriaId, string $nome): int
    {
        return (int) DB::table('categorias')->insertGetId([
            'user_id' => $userId,
            'parent_id' => $categoriaId,
            'nome' => $nome,
            'icone' => 'tag',
            'tipo' => 'despesa',
            'is_seeded' => 0,
            'ordem' => 0,
        ]);
    }

    private function createLancamento(int $userId, int $contaId, int $cartaoId): int
    {
        return (int) DB::table('lancamentos')->insertGetId([
            'user_id' => $userId,
            'tipo' => 'despesa',
            'data' => '2026-04-01',
            'categoria_id' => null,
            'subcategoria_id' => null,
            'conta_id' => $contaId,
            'cartao_credito_id' => $cartaoId,
            'descricao' => 'Pagamento item',
            'valor' => '120.00',
            'pago' => 1,
            'data_pagamento' => '2026-04-02',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function createFaturaItem(int $userId, int $cartaoId, int $faturaId, array $overrides): int
    {
        return (int) DB::table('faturas_cartao_itens')->insertGetId(array_merge([
            'user_id' => $userId,
            'cartao_credito_id' => $cartaoId,
            'fatura_id' => $faturaId,
            'lancamento_id' => null,
            'descricao' => 'Item',
            'valor' => 100,
            'tipo' => 'despesa',
            'data_compra' => '2026-04-01',
            'data_vencimento' => '2026-05-10',
            'mes_referencia' => 4,
            'ano_referencia' => 2026,
            'categoria_id' => null,
            'subcategoria_id' => null,
            'eh_parcelado' => 0,
            'parcela_atual' => 1,
            'total_parcelas' => 1,
            'item_pai_id' => null,
            'pago' => 0,
            'data_pagamento' => null,
            'recorrente' => 0,
            'recorrencia_freq' => null,
            'recorrencia_fim' => null,
            'recorrencia_pai_id' => null,
            'cancelado_em' => null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], $overrides));
    }
}
