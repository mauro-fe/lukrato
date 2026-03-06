<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Application\Models\Lancamento;
use Application\Repositories\LancamentoRepository;
use Application\Services\Lancamento\LancamentoStatusService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class LancamentoStatusServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private LancamentoStatusService $service;
    private $lancamentoRepo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->lancamentoRepo = Mockery::mock(LancamentoRepository::class);
        $this->service = new LancamentoStatusService($this->lancamentoRepo);
    }

    // ─── marcarPago ─────────────────────────────────────────

    public function testMarcarPagoThrowsIfAlreadyPago(): void
    {
        $lancamento = new Lancamento();
        $lancamento->pago = true;

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('já está pago');

        $this->service->marcarPago($lancamento);
    }

    public function testMarcarPagoThrowsIfCancelado(): void
    {
        // Use setRawAttributes to avoid Eloquent needing a DB connection for casting
        $lancamento = new Lancamento();
        $lancamento->setRawAttributes(['pago' => 0, 'cancelado_em' => '2026-03-01'], true);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('cancelado');

        $this->service->marcarPago($lancamento);
    }

    // ─── desmarcarPago ──────────────────────────────────────

    public function testDesmarcarPagoThrowsIfAlreadyPendente(): void
    {
        $lancamento = new Lancamento();
        $lancamento->pago = false;

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('pendente');

        $this->service->desmarcarPago($lancamento);
    }

    public function testDesmarcarPagoThrowsIfOrigemProtegida(): void
    {
        $lancamento = new Lancamento();
        $lancamento->pago = true;
        $lancamento->origem_tipo = Lancamento::ORIGEM_PAGAMENTO_FATURA;

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('não pode ser desmarcado');

        $this->service->desmarcarPago($lancamento);
    }

    // ─── buildPagoPayload ───────────────────────────────────

    public function testBuildPagoPayloadPago(): void
    {
        $payload = $this->service->buildPagoPayload(true);

        $this->assertEquals(1, $payload['pago']);
        $this->assertNotNull($payload['data_pagamento']);
        $this->assertEquals(date('Y-m-d'), $payload['data_pagamento']);
    }

    public function testBuildPagoPayloadNaoPago(): void
    {
        $payload = $this->service->buildPagoPayload(false);

        $this->assertEquals(0, $payload['pago']);
        $this->assertNull($payload['data_pagamento']);
    }

    // ─── Constructor DI ─────────────────────────────────────

    public function testConstructorAcceptsNull(): void
    {
        $service = new LancamentoStatusService();
        $this->assertInstanceOf(LancamentoStatusService::class, $service);
    }
}
