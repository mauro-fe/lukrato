<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Plan;

use Application\Services\Plan\PlanLimitService;
use Illuminate\Database\Capsule\Manager as DB;
use PHPUnit\Framework\TestCase;

class PlanLimitServiceTest extends TestCase
{
    /**
     * @var array<int, int>
     */
    private array $cleanupUserIds = [];

    protected function tearDown(): void
    {
        if ($this->cleanupUserIds !== []) {
            DB::table('importacao_lotes')->whereIn('user_id', $this->cleanupUserIds)->delete();
            DB::table('usuarios')->whereIn('id', $this->cleanupUserIds)->delete();
        }

        $this->cleanupUserIds = [];
        parent::tearDown();
    }

    public function testResolveImportLimitBucketMapping(): void
    {
        $service = new PlanLimitService();

        $this->assertSame('import_conta_ofx', $service->resolveImportLimitBucket('ofx', 'conta'));
        $this->assertSame('import_conta_csv', $service->resolveImportLimitBucket('csv', 'conta'));
        $this->assertSame('import_cartao_ofx', $service->resolveImportLimitBucket('ofx', 'cartao'));
        $this->assertSame('import_cartao_ofx', $service->resolveImportLimitBucket('csv', 'cartao'));
    }

    public function testCanUseImportacaoBlocksFreeAfterOneConfirmedCardImport(): void
    {
        $this->ensureDatabaseAvailable();

        $userId = $this->createUser();
        DB::table('importacao_lotes')->insert([
            'user_id' => $userId,
            'conta_id' => 1,
            'source_type' => 'ofx',
            'filename' => 'fatura.ofx',
            'status' => 'processed',
            'total_rows' => 2,
            'imported_rows' => 2,
            'duplicate_rows' => 0,
            'error_rows' => 0,
            'meta_json' => json_encode(['import_target' => 'cartao'], JSON_UNESCAPED_UNICODE),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $service = new PlanLimitService();
        $result = $service->canUseImportacao($userId, 'ofx', 'cartao');

        $this->assertFalse((bool) ($result['allowed'] ?? true));
        $this->assertSame('import_cartao_ofx', $result['bucket'] ?? null);
        $this->assertSame(1, (int) ($result['limit'] ?? 0));
        $this->assertSame(1, (int) ($result['used'] ?? 0));
        $this->assertSame(0, (int) ($result['remaining'] ?? 1));
    }

    public function testCanUseImportacaoIgnoresFailedAndZeroImportedRows(): void
    {
        $this->ensureDatabaseAvailable();

        $userId = $this->createUser();
        DB::table('importacao_lotes')->insert([
            [
                'user_id' => $userId,
                'conta_id' => 1,
                'source_type' => 'csv',
                'filename' => 'falhou.csv',
                'status' => 'failed',
                'total_rows' => 5,
                'imported_rows' => 2,
                'duplicate_rows' => 0,
                'error_rows' => 3,
                'meta_json' => json_encode(['import_target' => 'conta'], JSON_UNESCAPED_UNICODE),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'user_id' => $userId,
                'conta_id' => 1,
                'source_type' => 'csv',
                'filename' => 'duplicado.csv',
                'status' => 'processed_duplicates_only',
                'total_rows' => 4,
                'imported_rows' => 0,
                'duplicate_rows' => 4,
                'error_rows' => 0,
                'meta_json' => json_encode(['import_target' => 'conta'], JSON_UNESCAPED_UNICODE),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ]);

        $service = new PlanLimitService();
        $result = $service->canUseImportacao($userId, 'csv', 'conta');

        $this->assertTrue((bool) ($result['allowed'] ?? false));
        $this->assertSame('import_conta_csv', $result['bucket'] ?? null);
        $this->assertSame(0, (int) ($result['used'] ?? 1));
        $this->assertSame(1, (int) ($result['remaining'] ?? 0));
    }

    private function ensureDatabaseAvailable(): void
    {
        try {
            DB::connection()->getPdo();
        } catch (\Throwable) {
            $this->markTestSkipped('Database connection required for plan limit tests');
        }
    }

    private function createUser(): int
    {
        $email = 'plan-limit-' . bin2hex(random_bytes(5)) . '@example.com';
        $id = (int) DB::table('usuarios')->insertGetId([
            'nome' => 'Plan Limit Test',
            'email' => $email,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->cleanupUserIds[] = $id;

        return $id;
    }
}
