<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Financeiro;

use Application\Models\Meta;
use Application\Repositories\MetaRepository;
use Application\Services\Conta\ContaService;
use Application\Services\Financeiro\MetaService;
use Application\Services\Plan\PlanLimitService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class MetaServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testCriarUsesPlanLimitAndInitializesLinkedAccountBalance(): void
    {
        $repo = Mockery::mock(MetaRepository::class);
        $planLimit = Mockery::mock(PlanLimitService::class);
        $contaService = Mockery::mock(ContaService::class);

        $planLimit->shouldReceive('assertCanCreateMeta')
            ->once()
            ->with(77);

        $contaService->shouldReceive('getSaldoAtual')
            ->twice()
            ->with(12, 77)
            ->andReturn(450.0);

        $meta = $this->makeMeta([
            'id' => 9,
            'titulo' => 'Reserva',
            'tipo' => Meta::TIPO_EMERGENCIA,
            'valor_alvo' => 1000.0,
            'valor_atual' => 450.0,
            'conta_id' => 12,
            'status' => Meta::STATUS_ATIVA,
            'data_inicio' => Carbon::parse('2026-03-18'),
        ]);

        $repo->shouldReceive('createForUser')
            ->once()
            ->with(77, Mockery::on(function (array $data): bool {
                return $data['user_id'] === 77
                    && $data['status'] === Meta::STATUS_ATIVA
                    && $data['conta_id'] === 12
                    && $data['valor_atual'] === 450.0
                    && preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['data_inicio']) === 1;
            }))
            ->andReturn($meta);

        $service = $this->makeService($repo, $planLimit, $contaService);

        $result = $service->criar(77, [
            'titulo' => 'Reserva',
            'tipo' => Meta::TIPO_EMERGENCIA,
            'valor_alvo' => 1000.0,
            'conta_id' => 12,
        ]);

        $this->assertSame('Reserva', $result['titulo']);
        $this->assertSame(450.0, $result['valor_atual']);
        $this->assertSame(Meta::STATUS_ATIVA, $result['status']);
        $this->assertSame(0, $meta->saveCalls);
    }

    public function testAtualizarAutoConcludesManualGoalWhenTargetIsReached(): void
    {
        $meta = $this->makeMeta([
            'id' => 13,
            'titulo' => 'Viagem',
            'tipo' => Meta::TIPO_VIAGEM,
            'valor_alvo' => 1000.0,
            'valor_atual' => 80.0,
            'conta_id' => null,
            'status' => Meta::STATUS_ATIVA,
            'data_inicio' => Carbon::parse('2026-01-01'),
        ]);

        $repo = Mockery::mock(MetaRepository::class);
        $repo->shouldReceive('findByIdAndUser')
            ->once()
            ->with(13, 77)
            ->andReturn($meta);

        $service = $this->makeService($repo);

        $result = $service->atualizar(77, 13, ['valor_atual' => 1000.0]);

        $this->assertSame(1000.0, $result['valor_atual']);
        $this->assertSame(Meta::STATUS_CONCLUIDA, $result['status']);
        $this->assertSame([['valor_atual' => 1000.0], ['status' => Meta::STATUS_CONCLUIDA]], $meta->updateCalls);
    }

    public function testAdicionarAporteRejectsAccountLinkedGoal(): void
    {
        $meta = $this->makeMeta([
            'id' => 21,
            'titulo' => 'Entrada imóvel',
            'tipo' => Meta::TIPO_MORADIA,
            'valor_alvo' => 50000.0,
            'valor_atual' => 12000.0,
            'conta_id' => 5,
            'status' => Meta::STATUS_ATIVA,
        ]);

        $repo = Mockery::mock(MetaRepository::class);
        $repo->shouldReceive('findByIdAndUser')
            ->once()
            ->with(21, 77)
            ->andReturn($meta);

        $service = $this->makeService($repo);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Esta meta está vinculada a uma conta');

        $service->adicionarAporte(77, 21, 500.0);
    }

    public function testAdicionarAporteAutoConcludesReachedGoal(): void
    {
        $meta = $this->makeMeta([
            'id' => 31,
            'titulo' => 'Notebook',
            'tipo' => Meta::TIPO_COMPRA,
            'valor_alvo' => 100.0,
            'valor_atual' => 80.0,
            'conta_id' => null,
            'status' => Meta::STATUS_ATIVA,
        ]);

        $repo = Mockery::mock(MetaRepository::class);
        $repo->shouldReceive('findByIdAndUser')
            ->times(3)
            ->with(31, 77)
            ->andReturn($meta);

        $repo->shouldReceive('atualizarValor')
            ->once()
            ->with(31, 77, 105.0)
            ->andReturnUsing(function () use ($meta): bool {
                $meta->valor_atual = 105.0;
                return true;
            });

        $service = $this->makeService($repo);

        $result = $service->adicionarAporte(77, 31, 25.0);

        $this->assertSame(105.0, $result['valor_atual']);
        $this->assertSame(Meta::STATUS_CONCLUIDA, $result['status']);
        $this->assertSame([['status' => Meta::STATUS_CONCLUIDA]], $meta->updateCalls);
    }

    public function testResumoSynchronizesBalancesAndBuildsAggregatePayload(): void
    {
        $linked = $this->makeMeta([
            'id' => 41,
            'titulo' => 'Reserva vinculada',
            'tipo' => Meta::TIPO_EMERGENCIA,
            'valor_alvo' => 500.0,
            'valor_atual' => 100.0,
            'conta_id' => 8,
            'status' => Meta::STATUS_ATIVA,
            'data_inicio' => Carbon::parse('2026-01-01'),
        ]);
        $manual = $this->makeMeta([
            'id' => 42,
            'titulo' => 'Viagem',
            'tipo' => Meta::TIPO_VIAGEM,
            'valor_alvo' => 400.0,
            'valor_atual' => 320.0,
            'conta_id' => null,
            'status' => Meta::STATUS_ATIVA,
            'data_inicio' => Carbon::parse('2026-02-01'),
        ]);
        $manual->forcedAtrasada = true;

        $repo = Mockery::mock(MetaRepository::class);
        $repo->shouldReceive('findByUser')
            ->with(77)
            ->once()
            ->andReturn(new EloquentCollection([$linked, $manual]));
        $repo->shouldReceive('findByUser')
            ->with(77, Meta::STATUS_ATIVA)
            ->once()
            ->andReturn(new EloquentCollection([$linked, $manual]));

        $contaService = Mockery::mock(ContaService::class);
        $contaService->shouldReceive('getSaldoAtual')
            ->once()
            ->with(8, 77)
            ->andReturn(250.0);

        $service = $this->makeService($repo, null, $contaService);

        $summary = $service->resumo(77);

        $this->assertSame(2, $summary['total_metas']);
        $this->assertSame(900.0, $summary['total_alvo']);
        $this->assertSame(570.0, $summary['total_atual']);
        $this->assertSame(63.3, $summary['progresso_geral']);
        $this->assertSame(1, $summary['atrasadas']);
        $this->assertSame('Viagem', $summary['proxima_concluir']['titulo']);
        $this->assertSame(250.0, $linked->valor_atual);
        $this->assertSame(1, $linked->saveCalls);
    }

    public function testListarReactivatesCompletedLinkedGoalWhenBalanceDrops(): void
    {
        $linked = $this->makeMeta([
            'id' => 51,
            'titulo' => 'Reserva vinculada',
            'tipo' => Meta::TIPO_EMERGENCIA,
            'valor_alvo' => 1000.0,
            'valor_atual' => 1200.0,
            'conta_id' => 3,
            'status' => Meta::STATUS_CONCLUIDA,
            'data_inicio' => Carbon::parse('2026-01-01'),
        ]);

        $repo = Mockery::mock(MetaRepository::class);
        $repo->shouldReceive('findByUser')
            ->once()
            ->with(77, Meta::STATUS_CONCLUIDA)
            ->andReturn(new EloquentCollection([$linked]));

        $contaService = Mockery::mock(ContaService::class);
        $contaService->shouldReceive('getSaldoAtual')
            ->once()
            ->with(3, 77)
            ->andReturn(800.0);

        $service = $this->makeService($repo, null, $contaService);

        $result = $service->listar(77, Meta::STATUS_CONCLUIDA);

        $this->assertCount(1, $result);
        $this->assertSame(800.0, $result[0]['valor_atual']);
        $this->assertSame(Meta::STATUS_ATIVA, $result[0]['status']);
        $this->assertSame([['status' => Meta::STATUS_ATIVA]], $linked->updateCalls);
        $this->assertSame(1, $linked->saveCalls);
    }

    public function testObterReturnsNullWhenMetaIsMissingAndSynchronizesWhenFound(): void
    {
        $repo = Mockery::mock(MetaRepository::class);
        $repo->shouldReceive('findByIdAndUser')
            ->once()
            ->with(88, 77)
            ->andReturn(null);

        $service = $this->makeService($repo);

        $this->assertNull($service->obter(77, 88));

        $meta = $this->makeMeta([
            'id' => 89,
            'titulo' => 'Carro',
            'tipo' => Meta::TIPO_VEICULO,
            'valor_alvo' => 20000.0,
            'valor_atual' => 5000.0,
            'conta_id' => 4,
            'status' => Meta::STATUS_ATIVA,
            'data_inicio' => Carbon::parse('2026-01-01'),
        ]);

        $repo = Mockery::mock(MetaRepository::class);
        $repo->shouldReceive('findByIdAndUser')
            ->once()
            ->with(89, 77)
            ->andReturn($meta);

        $contaService = Mockery::mock(ContaService::class);
        $contaService->shouldReceive('getSaldoAtual')
            ->once()
            ->with(4, 77)
            ->andReturn(6500.0);

        $service = $this->makeService($repo, null, $contaService);

        $result = $service->obter(77, 89);

        $this->assertSame(6500.0, $result['valor_atual']);
        $this->assertSame(1, $meta->saveCalls);
    }

    public function testRemoverDelegatesToRepository(): void
    {
        $repo = Mockery::mock(MetaRepository::class);
        $repo->shouldReceive('deleteForUser')
            ->once()
            ->with(14, 77)
            ->andReturn(true);

        $service = $this->makeService($repo);

        $this->assertTrue($service->remover(77, 14));
    }

    public function testGetTemplatesReturnsExpectedCatalog(): void
    {
        $service = new MetaService();

        $templates = $service->getTemplates();

        $this->assertCount(9, $templates);
        $this->assertSame('Reserva de Emergência', $templates[0]['titulo']);
        $this->assertSame(Meta::TIPO_EMERGENCIA, $templates[0]['tipo']);
        $this->assertArrayHasKey('icone', $templates[0]);
        $this->assertArrayHasKey('cor', $templates[0]);
    }

    private function makeService(
        ?MetaRepository $repo = null,
        ?PlanLimitService $planLimit = null,
        ?ContaService $contaService = null
    ): MetaService {
        $service = new MetaService();

        $setter = \Closure::bind(function (
            ?MetaRepository $repo,
            ?PlanLimitService $planLimit,
            ?ContaService $contaService
        ): void {
            if ($repo !== null) {
                $this->repo = $repo;
            }
            if ($planLimit !== null) {
                $this->planLimit = $planLimit;
            }
            if ($contaService !== null) {
                $this->contaService = $contaService;
            }
        }, $service, MetaService::class);

        $setter($repo, $planLimit, $contaService);

        return $service;
    }

    private function makeMeta(array $attributes): TestMeta
    {
        $meta = new TestMeta();
        $meta->fill($attributes);
        $meta->exists = true;

        return $meta;
    }
}

final class TestMeta extends Meta
{
    public int $saveCalls = 0;
    public array $updateCalls = [];
    public ?bool $forcedAtrasada = null;

    public function save(array $options = []): bool
    {
        $this->saveCalls++;

        return true;
    }

    public function update(array $attributes = [], array $options = []): bool
    {
        $this->fill($attributes);
        $this->updateCalls[] = $attributes;

        return true;
    }

    public function fresh($with = []): static
    {
        return $this;
    }

    public function isAtrasada(): bool
    {
        return $this->forcedAtrasada ?? parent::isAtrasada();
    }
}
