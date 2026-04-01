<?php

declare(strict_types=1);

namespace Tests\Unit\UseCases\Lancamentos;

use Application\Models\Lancamento;
use Application\Repositories\CategoriaRepository;
use Application\Repositories\ContaRepository;
use Application\Repositories\LancamentoRepository;
use Application\Services\Financeiro\MetaProgressService;
use Application\Services\Lancamento\LancamentoLimitService;
use Application\Services\User\OnboardingProgressService;
use Application\UseCases\Lancamentos\CreateLancamentoUseCase;
use DomainException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class CreateLancamentoUseCaseTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testExecuteReturnsValidationFailWhenPayloadIsInvalid(): void
    {
        $limitService = Mockery::mock(LancamentoLimitService::class);
        $lancamentoRepo = Mockery::mock(LancamentoRepository::class);
        $categoriaRepo = Mockery::mock(CategoriaRepository::class);
        $contaRepo = Mockery::mock(ContaRepository::class);
        $metaProgressService = Mockery::mock(MetaProgressService::class);
        $onboardingProgressService = Mockery::mock(OnboardingProgressService::class);

        $limitService->shouldNotReceive('assertCanCreate');
        $lancamentoRepo->shouldNotReceive('create');
        $metaProgressService->shouldNotReceive('recalculateMeta');
        $onboardingProgressService->shouldNotReceive('markLancamentoCreated');

        $useCase = new CreateLancamentoUseCase(
            $limitService,
            $lancamentoRepo,
            $categoriaRepo,
            $contaRepo,
            $metaProgressService,
            $onboardingProgressService
        );

        $result = $useCase->execute(10, []);

        $this->assertTrue($result->isValidationError());
        $this->assertSame(422, $result->httpCode);
        $this->assertArrayHasKey('tipo', $result->data['errors'] ?? []);
        $this->assertArrayHasKey('data', $result->data['errors'] ?? []);
        $this->assertArrayHasKey('valor', $result->data['errors'] ?? []);
        $this->assertArrayHasKey('descricao', $result->data['errors'] ?? []);
    }

    public function testExecuteReturns402WhenPlanLimitBlocksCreation(): void
    {
        $limitService = Mockery::mock(LancamentoLimitService::class);
        $lancamentoRepo = Mockery::mock(LancamentoRepository::class);
        $categoriaRepo = Mockery::mock(CategoriaRepository::class);
        $contaRepo = Mockery::mock(ContaRepository::class);
        $metaProgressService = Mockery::mock(MetaProgressService::class);
        $onboardingProgressService = Mockery::mock(OnboardingProgressService::class);

        $contaRepo->shouldReceive('belongsToUser')->once()->with(22, 10)->andReturnTrue();
        $limitService->shouldReceive('assertCanCreate')
            ->once()
            ->with(10, '2026-03-31')
            ->andThrow(new DomainException('Limite mensal atingido.'));
        $lancamentoRepo->shouldNotReceive('create');
        $metaProgressService->shouldNotReceive('recalculateMeta');
        $onboardingProgressService->shouldNotReceive('markLancamentoCreated');

        $useCase = new CreateLancamentoUseCase(
            $limitService,
            $lancamentoRepo,
            $categoriaRepo,
            $contaRepo,
            $metaProgressService,
            $onboardingProgressService
        );

        $result = $useCase->execute(10, [
            'tipo' => 'receita',
            'data' => '2026-03-31',
            'valor' => 120.90,
            'descricao' => 'Recebimento',
            'conta_id' => 22,
        ]);

        $this->assertTrue($result->isError());
        $this->assertSame(402, $result->httpCode);
        $this->assertSame('Limite mensal atingido.', $result->message);
    }

    public function testExecuteReturnsSuccessPayloadWhenLancamentoIsCreated(): void
    {
        $limitService = Mockery::mock(LancamentoLimitService::class);
        $lancamentoRepo = Mockery::mock(LancamentoRepository::class);
        $categoriaRepo = Mockery::mock(CategoriaRepository::class);
        $contaRepo = Mockery::mock(ContaRepository::class);
        $metaProgressService = Mockery::mock(MetaProgressService::class);
        $onboardingProgressService = Mockery::mock(OnboardingProgressService::class);

        $contaRepo->shouldReceive('belongsToUser')->once()->with(22, 10)->andReturnTrue();
        $categoriaRepo->shouldReceive('belongsToUser')->once()->with(33, 10)->andReturnTrue();

        $limitService->shouldReceive('assertCanCreate')->once()->with(10, '2026-03-31')->andReturn(['ok' => true]);
        $lancamentoRepo->shouldReceive('create')->once()->andReturnUsing(static function (array $data): Lancamento {
            $lancamento = new Lancamento();
            $lancamento->id = 55;
            $lancamento->created_at = '2026-03-31 12:00:00';
            return $lancamento;
        });
        $metaProgressService->shouldNotReceive('recalculateMeta');
        $onboardingProgressService->shouldReceive('markLancamentoCreated')->once()->with(10, '2026-03-31 12:00:00');
        $limitService->shouldReceive('usage')->once()->with(10, '2026-03')->andReturn(['should_warn' => true]);
        $limitService->shouldReceive('getWarningMessage')->once()->with(['should_warn' => true])->andReturn('Perto do limite');
        $limitService->shouldReceive('getUpgradeCta')->once()->andReturn('/premium');

        $useCase = new CreateLancamentoUseCase(
            $limitService,
            $lancamentoRepo,
            $categoriaRepo,
            $contaRepo,
            $metaProgressService,
            $onboardingProgressService
        );

        $result = $useCase->execute(10, [
            'tipo' => 'despesa',
            'data' => '2026-03-31',
            'valor' => 'R$ 99,90',
            'descricao' => 'Mercado',
            'observacao' => 'Compras',
            'conta_id' => 22,
            'categoria_id' => 33,
            'forma_pagamento' => 'pix',
        ]);

        $this->assertFalse($result->isError());
        $this->assertSame(201, $result->httpCode);
        $this->assertSame('Lançamento criado', $result->message);
        $this->assertSame(55, $result->data['id'] ?? null);
        $this->assertSame('Perto do limite', $result->data['ui_message'] ?? null);
        $this->assertSame('/premium', $result->data['upgrade_cta'] ?? null);
    }
}
