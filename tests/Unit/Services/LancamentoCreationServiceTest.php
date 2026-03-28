<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Application\DTO\ServiceResultDTO;
use Application\Repositories\LancamentoRepository;
use Application\Services\Cartao\CartaoCreditoLancamentoService;
use Application\Services\Gamification\GamificationService;
use Application\Services\Lancamento\LancamentoCreationService;
use Application\Services\Lancamento\LancamentoLimitService;
use Application\Services\Plan\UserPlanService;
use Application\Validators\LancamentoValidator;
use Illuminate\Database\Capsule\Manager as DB;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class LancamentoCreationServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private LancamentoCreationService $service;
    private $cartaoService;
    private $lancamentoRepo;
    private $gamificationService;
    private $limitService;
    private $planService;
    private array $cleanupUserIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->cartaoService = Mockery::mock(CartaoCreditoLancamentoService::class);
        $this->lancamentoRepo = Mockery::mock(LancamentoRepository::class);
        $this->gamificationService = Mockery::mock(GamificationService::class);
        $this->limitService = Mockery::mock(LancamentoLimitService::class);
        $this->planService = Mockery::mock(UserPlanService::class);

        $this->service = new LancamentoCreationService(
            $this->cartaoService,
            $this->lancamentoRepo,
            $this->gamificationService,
            $this->limitService,
            $this->planService,
        );
    }

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

    // ─── Validação ──────────────────────────────────────────

    public function testCreateFromPayloadReturnsValidationFailOnInvalidData(): void
    {
        // Arrange: payload sem tipo e sem valor
        $payload = [
            'tipo'      => '',
            'data'      => '2026-03-06',
            'valor'     => '',
            'descricao' => 'Teste',
            'conta_id'  => 1,
        ];

        // DB::transaction requer conexão ativa — pular se não disponível
        try {
            DB::connection()->getPdo();
        } catch (\Throwable) {
            $this->markTestSkipped('Database connection required for transaction test');
        }

        $result = $this->service->createFromPayload(1, $payload);

        $this->assertInstanceOf(ServiceResultDTO::class, $result);
        $this->assertFalse($result->success);
        $this->assertEquals(422, $result->httpCode);
    }

    // ─── sanitizeValor via Validator ────────────────────────

    public function testSanitizeValorIntegration(): void
    {
        $this->assertEquals(1500.50, LancamentoValidator::sanitizeValor('R$ 1.500,50'));
        $this->assertEquals(100.00, LancamentoValidator::sanitizeValor(-100));
        $this->assertEquals(0.0, LancamentoValidator::sanitizeValor(0));
    }

    // ─── Constructor DI ─────────────────────────────────────

    public function testConstructorAcceptsNullDependencies(): void
    {
        // Should not throw — all params are nullable with defaults
        $service = new LancamentoCreationService();
        $this->assertInstanceOf(LancamentoCreationService::class, $service);
    }

    public function testConstructorAcceptsInjectedDependencies(): void
    {
        $this->assertInstanceOf(LancamentoCreationService::class, $this->service);
    }

    public function testEstenderRecorrenciasInfinitasBackfillsAllOverdueSlotsInOneRun(): void
    {
        $this->ensureDatabaseAvailable();

        $service = new LancamentoCreationService();
        $userId = $this->createUser();
        $contaId = $this->createConta($userId);
        $dataInicial = (new \DateTimeImmutable('first day of this month'))
            ->sub(new \DateInterval('P3M'))
            ->format('Y-m-d');

        $paiId = $this->createRecurringParent($userId, $contaId, $dataInicial, null);

        $criados = $service->estenderRecorrenciasInfinitas();

        $datas = DB::table('lancamentos')
            ->where('recorrencia_pai_id', $paiId)
            ->orderBy('data')
            ->pluck('data')
            ->map(static fn($data) => (string) $data)
            ->all();

        $this->assertSame(3, $criados);
        $this->assertSame([
            $dataInicial,
            $this->advanceMonths($dataInicial, 1),
            $this->advanceMonths($dataInicial, 2),
            $this->advanceMonths($dataInicial, 3),
        ], $datas);
    }

    public function testEstenderRecorrenciasInfinitasSkipsDeletedSlotAndContinuesSeries(): void
    {
        $this->ensureDatabaseAvailable();

        $service = new LancamentoCreationService();
        $userId = $this->createUser();
        $contaId = $this->createConta($userId);
        $dataInicial = (new \DateTimeImmutable('first day of this month'))
            ->sub(new \DateInterval('P2M'))
            ->format('Y-m-d');

        $paiId = $this->createRecurringParent($userId, $contaId, $dataInicial, null);
        $this->createRecurringOccurrence(
            $userId,
            $contaId,
            $paiId,
            $this->advanceMonths($dataInicial, 1),
            date('Y-m-d H:i:s')
        );

        $criados = $service->estenderRecorrenciasInfinitas();

        $datas = DB::table('lancamentos')
            ->where('recorrencia_pai_id', $paiId)
            ->orderBy('data')
            ->pluck('data')
            ->map(static fn($data) => (string) $data)
            ->all();

        $this->assertSame(1, $criados);
        $this->assertSame([
            $dataInicial,
            $this->advanceMonths($dataInicial, 1),
            $this->advanceMonths($dataInicial, 2),
        ], $datas);
    }

    public function testEstenderRecorrenciasInfinitasDoesNotCompensateDeletedSlotInFiniteSeries(): void
    {
        $this->ensureDatabaseAvailable();

        $service = new LancamentoCreationService();
        $userId = $this->createUser();
        $contaId = $this->createConta($userId);
        $dataInicial = (new \DateTimeImmutable('first day of this month'))
            ->sub(new \DateInterval('P4M'))
            ->format('Y-m-d');

        $paiId = $this->createRecurringParent($userId, $contaId, $dataInicial, 4);
        $this->createRecurringOccurrence(
            $userId,
            $contaId,
            $paiId,
            $this->advanceMonths($dataInicial, 1),
            date('Y-m-d H:i:s')
        );
        $this->createRecurringOccurrence($userId, $contaId, $paiId, $this->advanceMonths($dataInicial, 2));
        $this->createRecurringOccurrence($userId, $contaId, $paiId, $this->advanceMonths($dataInicial, 3));

        $criados = $service->estenderRecorrenciasInfinitas();

        $datas = DB::table('lancamentos')
            ->where('recorrencia_pai_id', $paiId)
            ->orderBy('data')
            ->pluck('data')
            ->map(static fn($data) => (string) $data)
            ->all();

        $this->assertSame(0, $criados);
        $this->assertSame([
            $dataInicial,
            $this->advanceMonths($dataInicial, 1),
            $this->advanceMonths($dataInicial, 2),
            $this->advanceMonths($dataInicial, 3),
        ], $datas);
        $this->assertNotContains($this->advanceMonths($dataInicial, 4), $datas);
    }

    private function ensureDatabaseAvailable(): void
    {
        try {
            DB::connection()->getPdo();
        } catch (\Throwable) {
            $this->markTestSkipped('Database connection required for recurring lancamentos tests');
        }
    }

    private function createUser(): int
    {
        $email = 'recorrencia-test-' . bin2hex(random_bytes(6)) . '@example.com';
        $userId = (int) DB::table('usuarios')->insertGetId([
            'nome' => 'Teste Recorrencia',
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
            'nome' => 'Conta Teste',
            'tipo_conta' => 'conta_corrente',
            'saldo_inicial' => 0,
            'ativo' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function createRecurringParent(int $userId, int $contaId, string $data, ?int $recorrenciaTotal): int
    {
        $id = (int) DB::table('lancamentos')->insertGetId([
            'user_id' => $userId,
            'tipo' => 'despesa',
            'data' => $data,
            'valor' => 120.50,
            'descricao' => 'Recorrencia teste',
            'conta_id' => $contaId,
            'origem_tipo' => 'recorrencia',
            'recorrente' => 1,
            'recorrencia_freq' => 'mensal',
            'recorrencia_total' => $recorrenciaTotal,
            'pago' => 0,
            'afeta_caixa' => 0,
            'canal_email' => 0,
            'canal_inapp' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        DB::table('lancamentos')->where('id', $id)->update([
            'recorrencia_pai_id' => $id,
        ]);

        return $id;
    }

    private function createRecurringOccurrence(int $userId, int $contaId, int $paiId, string $data, ?string $deletedAt = null): int
    {
        return (int) DB::table('lancamentos')->insertGetId([
            'user_id' => $userId,
            'tipo' => 'despesa',
            'data' => $data,
            'valor' => 120.50,
            'descricao' => 'Recorrencia teste',
            'conta_id' => $contaId,
            'origem_tipo' => 'recorrencia',
            'recorrente' => 1,
            'recorrencia_freq' => 'mensal',
            'recorrencia_pai_id' => $paiId,
            'pago' => 0,
            'afeta_caixa' => 0,
            'canal_email' => 0,
            'canal_inapp' => 1,
            'deleted_at' => $deletedAt,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function advanceMonths(string $data, int $months): string
    {
        $date = new \DateTime($data);
        $date->add(new \DateInterval('P' . $months . 'M'));

        return $date->format('Y-m-d');
    }
}
