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
}
