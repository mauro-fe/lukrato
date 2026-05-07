<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Report;

use Application\DTO\ReportParameters;
use Application\Repositories\ReportRepository;
use Carbon\Carbon;
use Illuminate\Database\Capsule\Manager as DB;
use PHPUnit\Framework\TestCase;

class ReportRepositoryTest extends TestCase
{
    private array $cleanupUserIds = [];

    protected function tearDown(): void
    {
        if ($this->cleanupUserIds !== []) {
            DB::table('lancamentos')->whereIn('user_id', $this->cleanupUserIds)->delete();
            DB::table('contas')->whereIn('user_id', $this->cleanupUserIds)->delete();
            DB::table('usuarios')->whereIn('id', $this->cleanupUserIds)->delete();
        }

        $this->cleanupUserIds = [];

        parent::tearDown();
    }

    public function testMonthlyAndCategoryReportsIgnoreSoftDeletedLancamentos(): void
    {
        $this->ensureDatabaseAvailable();

        $repository = new ReportRepository();
        $userId = $this->createUser();
        $contaId = $this->createConta($userId);

        $this->createLancamento($userId, $contaId, 'receita', '2026-01-10', '100.00');
        $this->createLancamento($userId, $contaId, 'receita', '2026-01-12', '9999.99', date('Y-m-d H:i:s'));
        $this->createLancamento($userId, $contaId, 'despesa', '2026-01-15', '40.00');

        $params = ReportParameters::forMonth(2026, 1, null, $userId);

        $daily = $repository->getDailyDelta($params, $params->useTransfers());
        $deltaDia10 = (float) ($daily->get('2026-01-10')->delta ?? 0.0);
        $deltaDia12 = (float) ($daily->get('2026-01-12')->delta ?? 0.0);

        $categorias = $repository->getCategoryTotals('receita', $params);
        $totalReceitas = (float) $categorias->sum(fn($row) => (float) $row->total);

        $saldoAteFimDoMes = $repository->saldoAte(Carbon::create(2026, 1, 31)->endOfDay(), $params, false);

        $this->assertSame(100.0, $deltaDia10);
        $this->assertSame(0.0, $deltaDia12);
        $this->assertSame(100.0, $totalReceitas);
        $this->assertSame(60.0, $saldoAteFimDoMes);
    }

    private function ensureDatabaseAvailable(): void
    {
        try {
            DB::connection()->getPdo();
        } catch (\Throwable) {
            $this->markTestSkipped('Database connection required for report repository tests');
        }
    }

    private function createUser(): int
    {
        $email = 'report-repository-test-' . bin2hex(random_bytes(6)) . '@example.com';
        $userId = (int) DB::table('usuarios')->insertGetId([
            'nome' => 'Teste ReportRepository',
            'email' => $email,
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
            'nome' => 'Conta Teste Relatorio',
            'tipo_conta' => 'conta_corrente',
            'saldo_inicial' => 0,
            'ativo' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function createLancamento(
        int $userId,
        int $contaId,
        string $tipo,
        string $data,
        string $valor,
        ?string $deletedAt = null
    ): int {
        return (int) DB::table('lancamentos')->insertGetId([
            'user_id' => $userId,
            'tipo' => $tipo,
            'data' => $data,
            'valor' => $valor,
            'descricao' => 'Lancamento teste relatorio',
            'conta_id' => $contaId,
            'pago' => 1,
            'afeta_caixa' => 1,
            'eh_saldo_inicial' => 0,
            'eh_transferencia' => 0,
            'canal_email' => 0,
            'canal_inapp' => 1,
            'deleted_at' => $deletedAt,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
