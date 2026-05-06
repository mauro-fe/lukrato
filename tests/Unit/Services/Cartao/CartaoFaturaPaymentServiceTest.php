<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Cartao;

use Application\Services\Cartao\CartaoFaturaPaymentService;
use Illuminate\Database\Capsule\Manager as DB;
use PHPUnit\Framework\TestCase;

class CartaoFaturaPaymentServiceTest extends TestCase
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

    public function testPagarFaturaUsaTotalLiquidoQuandoExisteEstornoPago(): void
    {
        $this->ensureDatabaseAvailable();

        $service = new CartaoFaturaPaymentService();
        $userId = $this->createUser();
        $contaId = $this->createConta($userId, 2000);
        $cartaoId = $this->createCartao($userId, $contaId, 2000);
        $faturaId = $this->createFatura($userId, $cartaoId, 1000);

        $despesaId = $this->createFaturaItem($userId, $cartaoId, $faturaId, [
            'descricao' => 'Compra principal',
            'valor' => 1030,
            'tipo' => 'despesa',
            'pago' => 0,
            'data_pagamento' => null,
        ]);

        $estornoId = $this->createFaturaItem($userId, $cartaoId, $faturaId, [
            'descricao' => 'Estorno',
            'valor' => -30,
            'tipo' => 'estorno',
            'pago' => 1,
            'data_pagamento' => '2026-05-02',
        ]);

        $result = $service->pagarFatura($cartaoId, 5, 2026, $userId, $contaId);

        $this->assertTrue($result['success']);
        $this->assertSame(1000.0, (float) $result['valor_pago']);
        $this->assertSame(0.0, (float) $result['valor_restante']);

        $lancamentoPagamento = DB::table('lancamentos')
            ->where('user_id', $userId)
            ->where('origem_tipo', 'pagamento_fatura')
            ->orderByDesc('id')
            ->first();

        $this->assertNotNull($lancamentoPagamento);
        $this->assertSame(1000.0, (float) ($lancamentoPagamento->valor ?? 0));
        $this->assertSame(1, (int) DB::table('faturas_cartao_itens')->where('id', $despesaId)->value('pago'));
        $this->assertSame(1, (int) DB::table('faturas_cartao_itens')->where('id', $estornoId)->value('pago'));
    }

    private function ensureDatabaseAvailable(): void
    {
        try {
            DB::connection()->getPdo();
        } catch (\Throwable) {
            $this->markTestSkipped('Database connection required for invoice payment tests');
        }
    }

    private function createUser(): int
    {
        $userId = (int) DB::table('usuarios')->insertGetId([
            'nome' => 'Usuario Pagamento Fatura',
            'email' => 'fatura-pay-' . bin2hex(random_bytes(5)) . '@example.com',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->cleanupUserIds[] = $userId;

        return $userId;
    }

    private function createConta(int $userId, float $saldoInicial): int
    {
        return (int) DB::table('contas')->insertGetId([
            'user_id' => $userId,
            'nome' => 'Conta Pagamento',
            'instituicao' => 'Banco Teste',
            'tipo_conta' => 'corrente',
            'saldo_inicial' => number_format($saldoInicial, 2, '.', ''),
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
            'nome_cartao' => 'Cartao Pagamento',
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
            'descricao' => 'Fatura 5/2026',
            'valor_total' => number_format($valorTotal, 2, '.', ''),
            'numero_parcelas' => 0,
            'data_compra' => '2026-05-01',
            'status' => 'pendente',
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
            'data_compra' => '2026-05-01',
            'data_vencimento' => '2026-05-10',
            'mes_referencia' => 5,
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
