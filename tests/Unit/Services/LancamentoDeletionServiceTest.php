<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Application\Models\Lancamento;
use Application\Repositories\LancamentoRepository;
use Application\Repositories\ParcelamentoRepository;
use Application\Services\Lancamento\LancamentoDeletionService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class LancamentoDeletionServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private LancamentoDeletionService $service;
    private $lancamentoRepo;
    private $parcelamentoRepo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->lancamentoRepo = Mockery::mock(LancamentoRepository::class);
        $this->parcelamentoRepo = Mockery::mock(ParcelamentoRepository::class);
        $this->service = new LancamentoDeletionService(
            $this->lancamentoRepo,
            $this->parcelamentoRepo,
        );
    }

    // ─── Constructor ────────────────────────────────────────

    public function testConstructorAcceptsNullDependencies(): void
    {
        $service = new LancamentoDeletionService();
        $this->assertInstanceOf(LancamentoDeletionService::class, $service);
    }

    public function testConstructorAcceptsInjectedDependencies(): void
    {
        $this->assertInstanceOf(LancamentoDeletionService::class, $this->service);
    }

    // ─── Scope validation ───────────────────────────────────

    public function testDeleteMethodExists(): void
    {
        $this->assertTrue(
            method_exists($this->service, 'delete'),
            'LancamentoDeletionService deve ter método delete()'
        );
    }

    public function testDeleteMethodSignature(): void
    {
        $reflection = new \ReflectionMethod(LancamentoDeletionService::class, 'delete');
        $params = $reflection->getParameters();

        $this->assertEquals('lancamento', $params[0]->getName());
        $this->assertEquals('userId', $params[1]->getName());
        $this->assertEquals('scope', $params[2]->getName());
        $this->assertEquals('single', $params[2]->getDefaultValue());
    }

    public function testValidScopeValues(): void
    {
        $reflection = new \ReflectionMethod(LancamentoDeletionService::class, 'delete');
        $scopeParam = $reflection->getParameters()[2];

        // scope has a default of 'single'
        $this->assertTrue($scopeParam->isDefaultValueAvailable());
        $this->assertEquals('single', $scopeParam->getDefaultValue());
    }
}
