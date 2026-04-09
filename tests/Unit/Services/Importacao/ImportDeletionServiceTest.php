<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Importacao;

use Application\Models\ImportacaoJob;
use Application\Models\ImportacaoLote;
use Application\Models\Lancamento;
use Application\Services\Importacao\ImportDeletionService;
use Application\Services\Importacao\ImportHistoryService;
use Illuminate\Database\Capsule\Manager as DB;
use PHPUnit\Framework\TestCase;

class ImportDeletionServiceTest extends TestCase
{
    /**
     * @var array<int, int>
     */
    private array $cleanupUserIds = [];

    protected function tearDown(): void
    {
        if ($this->cleanupUserIds !== []) {
            DB::table('importacao_jobs')->whereIn('user_id', $this->cleanupUserIds)->delete();
            DB::table('importacao_itens')->whereIn('user_id', $this->cleanupUserIds)->delete();
            DB::table('importacao_lotes')->whereIn('user_id', $this->cleanupUserIds)->delete();
            DB::table('faturas_cartao_itens')->whereIn('user_id', $this->cleanupUserIds)->delete();
            DB::table('faturas')->whereIn('user_id', $this->cleanupUserIds)->delete();
            DB::table('lancamentos')->whereIn('user_id', $this->cleanupUserIds)->delete();
            DB::table('cartoes_credito')->whereIn('user_id', $this->cleanupUserIds)->delete();
            DB::table('contas')->whereIn('user_id', $this->cleanupUserIds)->delete();
            DB::table('usuarios')->whereIn('id', $this->cleanupUserIds)->delete();
        }

        $this->cleanupUserIds = [];
        parent::tearDown();
    }

    public function testDeleteBatchForContaRemovesIntactLancamentosTrackingAndJobs(): void
    {
        $this->ensureDatabaseAvailable();

        $userId = $this->createUser();
        $contaId = $this->createConta($userId);
        $batchId = $this->createBatch($userId, $contaId, 'ofx');
        $lancamentoId = $this->createLancamento($userId, $contaId, 'Mercado', 120.50, 'despesa', '2026-04-01', 'Compra do mes');

        $this->createImportItem($batchId, $userId, $contaId, [
            'lancamento_id' => $lancamentoId,
            'status' => 'imported',
            'data' => '2026-04-01',
            'amount' => 120.50,
            'tipo' => 'despesa',
            'description' => 'Mercado',
            'memo' => 'Compra do mes',
            'raw_json' => json_encode(['import_target' => 'conta'], JSON_UNESCAPED_UNICODE),
        ]);
        $this->createImportItem($batchId, $userId, $contaId, [
            'status' => 'duplicate',
            'data' => '2026-04-02',
            'amount' => 50,
            'tipo' => 'despesa',
            'description' => 'Duplicado',
            'raw_json' => json_encode(['import_target' => 'conta'], JSON_UNESCAPED_UNICODE),
        ]);
        $this->createJob($userId, $contaId, $batchId);

        $service = new ImportDeletionService();
        $result = $service->deleteBatchForUser($userId, $batchId);

        $this->assertTrue((bool) ($result['success'] ?? false));
        $this->assertTrue((bool) ($result['data']['batch_removed'] ?? false));
        $this->assertSame(2, (int) ($result['data']['deleted_count'] ?? 0));
        $this->assertSame(0, (int) ($result['data']['retained_count'] ?? -1));
        $this->assertNull(ImportacaoLote::query()->find($batchId));
        $this->assertSame(0, DB::table('importacao_itens')->where('lote_id', $batchId)->count());
        $this->assertSame(0, ImportacaoJob::query()->where('result_batch_id', $batchId)->count());
        $this->assertTrue((bool) Lancamento::withTrashed()->find($lancamentoId)?->trashed());
    }

    public function testDeleteBatchForContaPreservesChangedLancamentoAndKeepsBatchVisible(): void
    {
        $this->ensureDatabaseAvailable();

        $userId = $this->createUser();
        $contaId = $this->createConta($userId);
        $batchId = $this->createBatch($userId, $contaId, 'ofx');
        $intactLancamentoId = $this->createLancamento($userId, $contaId, 'Padaria', 25.90, 'despesa', '2026-04-02', 'Cafe da manha');
        $changedLancamentoId = $this->createLancamento($userId, $contaId, 'Academia', 90.00, 'despesa', '2026-04-03', 'Plano mensal');

        DB::table('lancamentos')->where('id', $changedLancamentoId)->update([
            'descricao' => 'Academia Premium',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->createImportItem($batchId, $userId, $contaId, [
            'lancamento_id' => $intactLancamentoId,
            'status' => 'imported',
            'data' => '2026-04-02',
            'amount' => 25.90,
            'tipo' => 'despesa',
            'description' => 'Padaria',
            'memo' => 'Cafe da manha',
            'raw_json' => json_encode(['import_target' => 'conta'], JSON_UNESCAPED_UNICODE),
        ]);
        $retainedItemId = $this->createImportItem($batchId, $userId, $contaId, [
            'lancamento_id' => $changedLancamentoId,
            'status' => 'imported',
            'data' => '2026-04-03',
            'amount' => 90,
            'tipo' => 'despesa',
            'description' => 'Academia',
            'memo' => 'Plano mensal',
            'raw_json' => json_encode(['import_target' => 'conta'], JSON_UNESCAPED_UNICODE),
        ]);
        $this->createImportItem($batchId, $userId, $contaId, [
            'status' => 'duplicate',
            'data' => '2026-04-04',
            'amount' => 12,
            'tipo' => 'despesa',
            'description' => 'Duplicado',
            'raw_json' => json_encode(['import_target' => 'conta'], JSON_UNESCAPED_UNICODE),
        ]);
        $this->createJob($userId, $contaId, $batchId);

        $service = new ImportDeletionService();
        $result = $service->deleteBatchForUser($userId, $batchId);

        $this->assertTrue((bool) ($result['success'] ?? false));
        $this->assertFalse((bool) ($result['data']['batch_removed'] ?? true));
        $this->assertSame(2, (int) ($result['data']['deleted_count'] ?? 0));
        $this->assertSame(1, (int) ($result['data']['retained_count'] ?? 0));
        $this->assertSame($retainedItemId, (int) ($result['data']['retained_items'][0]['item_id'] ?? 0));
        $this->assertTrue((bool) Lancamento::withTrashed()->find($intactLancamentoId)?->trashed());
        $this->assertFalse((bool) Lancamento::withTrashed()->find($changedLancamentoId)?->trashed());
        $this->assertSame(1, DB::table('importacao_itens')->where('lote_id', $batchId)->count());
        $this->assertSame(0, ImportacaoJob::query()->where('result_batch_id', $batchId)->count());

        $historyService = new ImportHistoryService();
        $historyBatch = $historyService->findForUser($userId, $batchId);

        $this->assertNotNull($historyBatch);
        $this->assertSame(1, (int) ($historyBatch['total_rows'] ?? 0));
        $this->assertSame(1, (int) ($historyBatch['retained_count'] ?? 0));
        $this->assertNotEmpty($historyBatch['partial_delete_summary'] ?? null);
    }

    public function testDeleteBatchForCartaoRemovesIntactInvoiceItemAndRecalculatesCard(): void
    {
        $this->ensureDatabaseAvailable();

        $userId = $this->createUser();
        $contaId = $this->createConta($userId);
        $cartaoId = $this->createCartao($userId, $contaId);
        $faturaId = $this->createFatura($userId, $cartaoId, 200);
        $faturaItemId = $this->createFaturaItem($userId, $cartaoId, $faturaId, [
            'descricao' => 'Restaurante',
            'valor' => 200,
            'tipo' => 'despesa',
            'data_compra' => '2026-04-01',
            'data_vencimento' => '2026-05-10',
            'mes_referencia' => 5,
            'ano_referencia' => 2026,
            'pago' => 0,
            'data_pagamento' => null,
        ]);
        $batchId = $this->createBatch($userId, $contaId, 'ofx', [
            'import_target' => 'cartao',
            'cartao_id' => $cartaoId,
            'cartao_nome' => 'Cartao Teste',
        ]);

        $this->createImportItem($batchId, $userId, $contaId, [
            'status' => 'imported',
            'data' => '2026-04-01',
            'amount' => 200,
            'tipo' => 'despesa',
            'description' => 'Restaurante',
            'raw_json' => json_encode([
                'import_target' => 'cartao',
                'cartao_id' => $cartaoId,
                'cartao_nome' => 'Cartao Teste',
                'fatura_id' => $faturaId,
                'fatura_item_id' => $faturaItemId,
            ], JSON_UNESCAPED_UNICODE),
        ]);
        $this->createJob($userId, $contaId, $batchId, $cartaoId);

        $service = new ImportDeletionService();
        $result = $service->deleteBatchForUser($userId, $batchId);

        $this->assertTrue((bool) ($result['success'] ?? false));
        $this->assertTrue((bool) ($result['data']['batch_removed'] ?? false));
        $this->assertSame(1, (int) ($result['data']['deleted_count'] ?? 0));
        $this->assertSame(0, DB::table('faturas_cartao_itens')->where('id', $faturaItemId)->count());
        $this->assertSame(0, DB::table('faturas')->where('id', $faturaId)->count());
        $this->assertSame(5000.0, (float) DB::table('cartoes_credito')->where('id', $cartaoId)->value('limite_disponivel'));
        $this->assertSame(0, ImportacaoJob::query()->where('result_batch_id', $batchId)->count());
    }

    public function testDeleteBatchForCartaoPreservesChangedInvoiceItem(): void
    {
        $this->ensureDatabaseAvailable();

        $userId = $this->createUser();
        $contaId = $this->createConta($userId);
        $cartaoId = $this->createCartao($userId, $contaId);
        $faturaId = $this->createFatura($userId, $cartaoId, 150);
        $faturaItemId = $this->createFaturaItem($userId, $cartaoId, $faturaId, [
            'descricao' => 'Restaurante Editado',
            'valor' => 150,
            'tipo' => 'despesa',
            'data_compra' => '2026-04-01',
            'data_vencimento' => '2026-05-10',
            'mes_referencia' => 5,
            'ano_referencia' => 2026,
            'pago' => 0,
            'data_pagamento' => null,
        ]);
        $batchId = $this->createBatch($userId, $contaId, 'ofx', [
            'import_target' => 'cartao',
            'cartao_id' => $cartaoId,
            'cartao_nome' => 'Cartao Teste',
        ]);

        $retainedItemId = $this->createImportItem($batchId, $userId, $contaId, [
            'status' => 'imported',
            'data' => '2026-04-01',
            'amount' => 150,
            'tipo' => 'despesa',
            'description' => 'Restaurante',
            'raw_json' => json_encode([
                'import_target' => 'cartao',
                'cartao_id' => $cartaoId,
                'cartao_nome' => 'Cartao Teste',
                'fatura_id' => $faturaId,
                'fatura_item_id' => $faturaItemId,
            ], JSON_UNESCAPED_UNICODE),
        ]);
        $this->createImportItem($batchId, $userId, $contaId, [
            'status' => 'duplicate',
            'data' => '2026-04-02',
            'amount' => 10,
            'tipo' => 'despesa',
            'description' => 'Duplicado',
            'raw_json' => json_encode([
                'import_target' => 'cartao',
                'cartao_id' => $cartaoId,
                'fatura_id' => $faturaId,
            ], JSON_UNESCAPED_UNICODE),
        ]);
        $this->createJob($userId, $contaId, $batchId, $cartaoId);

        $service = new ImportDeletionService();
        $result = $service->deleteBatchForUser($userId, $batchId);

        $this->assertTrue((bool) ($result['success'] ?? false));
        $this->assertFalse((bool) ($result['data']['batch_removed'] ?? true));
        $this->assertSame(1, (int) ($result['data']['deleted_count'] ?? 0));
        $this->assertSame(1, (int) ($result['data']['retained_count'] ?? 0));
        $this->assertSame($retainedItemId, (int) ($result['data']['retained_items'][0]['item_id'] ?? 0));
        $this->assertSame(1, DB::table('faturas_cartao_itens')->where('id', $faturaItemId)->count());
        $this->assertSame(1, DB::table('importacao_itens')->where('lote_id', $batchId)->count());
        $this->assertSame(0, ImportacaoJob::query()->where('result_batch_id', $batchId)->count());

        $historyService = new ImportHistoryService();
        $historyBatch = $historyService->findForUser($userId, $batchId);

        $this->assertNotNull($historyBatch);
        $this->assertSame(1, (int) ($historyBatch['retained_count'] ?? 0));
        $this->assertNotEmpty($historyBatch['partial_delete_summary'] ?? null);
    }

    private function ensureDatabaseAvailable(): void
    {
        try {
            DB::connection()->getPdo();
        } catch (\Throwable) {
            $this->markTestSkipped('Database connection required for importacao deletion tests');
        }
    }

    private function createUser(): int
    {
        $userId = (int) DB::table('usuarios')->insertGetId([
            'nome' => 'Usuario Exclusao',
            'email' => 'import-delete-' . bin2hex(random_bytes(5)) . '@example.com',
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
            'nome' => 'Conta Exclusao',
            'instituicao' => 'Banco Teste',
            'tipo_conta' => 'corrente',
            'saldo_inicial' => 0,
            'ativo' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @param array<string, mixed> $meta
     */
    private function createBatch(int $userId, int $contaId, string $sourceType, array $meta = []): int
    {
        return (int) DB::table('importacao_lotes')->insertGetId([
            'user_id' => $userId,
            'conta_id' => $contaId,
            'source_type' => $sourceType,
            'filename' => 'arquivo.ofx',
            'file_hash' => hash('sha256', uniqid('file-', true)),
            'status' => 'processed',
            'total_rows' => 2,
            'imported_rows' => 1,
            'duplicate_rows' => 1,
            'error_rows' => 0,
            'error_summary' => null,
            'meta_json' => $meta !== [] ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function createLancamento(
        int $userId,
        int $contaId,
        string $descricao,
        float $valor,
        string $tipo,
        string $data,
        string $memo = ''
    ): int {
        $observacao = 'Importado via OFX';
        if (trim($memo) !== '') {
            $observacao .= ' | ' . trim($memo);
        }

        return (int) DB::table('lancamentos')->insertGetId([
            'user_id' => $userId,
            'tipo' => $tipo,
            'data' => $data,
            'data_competencia' => $data,
            'conta_id' => $contaId,
            'descricao' => $descricao,
            'observacao' => $observacao,
            'valor' => number_format($valor, 2, '.', ''),
            'pago' => 1,
            'data_pagamento' => $data,
            'afeta_caixa' => 1,
            'afeta_competencia' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function createImportItem(int $batchId, int $userId, int $contaId, array $overrides): int
    {
        return (int) DB::table('importacao_itens')->insertGetId(array_merge([
            'lote_id' => $batchId,
            'user_id' => $userId,
            'conta_id' => $contaId,
            'lancamento_id' => null,
            'row_hash' => hash('sha256', uniqid('row-', true)),
            'status' => 'imported',
            'external_id' => null,
            'data' => '2026-04-01',
            'amount' => 10.00,
            'tipo' => 'despesa',
            'description' => 'Linha',
            'memo' => null,
            'raw_json' => null,
            'message' => null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], $overrides));
    }

    private function createJob(int $userId, int $contaId, int $batchId, ?int $cartaoId = null): int
    {
        return (int) DB::table('importacao_jobs')->insertGetId([
            'user_id' => $userId,
            'conta_id' => $contaId,
            'cartao_id' => $cartaoId,
            'source_type' => 'ofx',
            'import_target' => $cartaoId !== null ? 'cartao' : 'conta',
            'filename' => 'arquivo.ofx',
            'temp_file_path' => 'C:/tmp/arquivo.ofx',
            'status' => 'completed',
            'attempts' => 1,
            'started_at' => date('Y-m-d H:i:s', strtotime('-2 minutes')),
            'finished_at' => date('Y-m-d H:i:s', strtotime('-1 minutes')),
            'total_rows' => 1,
            'processed_rows' => 1,
            'imported_rows' => 1,
            'duplicate_rows' => 0,
            'error_rows' => 0,
            'result_batch_id' => $batchId,
            'error_summary' => null,
            'meta_json' => null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function createCartao(int $userId, int $contaId): int
    {
        return (int) DB::table('cartoes_credito')->insertGetId([
            'user_id' => $userId,
            'conta_id' => $contaId,
            'nome_cartao' => 'Cartao Teste',
            'bandeira' => 'visa',
            'ultimos_digitos' => '1234',
            'limite_total' => 5000,
            'limite_disponivel' => 4800,
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
            'data_compra' => '2026-04-01',
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
            'data_compra' => '2026-04-01',
            'data_vencimento' => '2026-05-10',
            'mes_referencia' => 5,
            'ano_referencia' => 2026,
            'categoria_id' => null,
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
