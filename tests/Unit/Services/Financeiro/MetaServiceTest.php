<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Financeiro;

use Application\Models\Meta;
use Application\Repositories\MetaRepository;
use Application\Services\Financeiro\MetaProgressService;
use Application\Services\Financeiro\MetaService;
use Application\Services\Plan\PlanLimitService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class MetaServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testCriarUsesPlanLimitAndInitializesManualAllocation(): void
    {
        $repo = Mockery::mock(MetaRepository::class);
        $planLimit = Mockery::mock(PlanLimitService::class);
        $progress = Mockery::mock(MetaProgressService::class);

        $planLimit->shouldReceive('assertCanCreateMeta')
            ->once()
            ->with(77);

        $repo->shouldReceive('createForUser')
            ->once()
            ->withArgs(function (int $userId, array $data): bool {
                return $userId === 77
                    && $data['titulo'] === 'Reserva'
                    && $data['valor_alvo'] === 1000.0
                    && $data['valor_alocado'] === 250.0
                    && $data['valor_aporte_manual'] === 250.0
                    && $data['conta_id'] === null
                    && $data['status'] === Meta::STATUS_ATIVA;
            })
            ->andReturn($this->makeMeta([
                'id' => 15,
                'titulo' => 'Reserva',
                'valor_alvo' => 1000.0,
                'valor_alocado' => 250.0,
                'valor_aporte_manual' => 250.0,
                'status' => Meta::STATUS_ATIVA,
            ]));

        $progress->shouldReceive('recalculateMeta')
            ->once()
            ->with(77, 15, true)
            ->andReturn($this->makeMeta([
                'id' => 15,
                'titulo' => 'Reserva',
                'valor_alvo' => 1000.0,
                'valor_alocado' => 250.0,
                'valor_aporte_manual' => 250.0,
                'status' => Meta::STATUS_ATIVA,
            ]));

        $service = new MetaService($repo, $planLimit, $progress);

        $result = $service->criar(77, [
            'titulo' => 'Reserva',
            'valor_alvo' => 1000.0,
            'valor_alocado' => 250.0,
        ]);

        $this->assertSame(15, $result['id']);
        $this->assertSame(250.0, $result['valor_alocado']);
        $this->assertSame(250.0, $result['valor_atual']);
        $this->assertSame(Meta::STATUS_ATIVA, $result['status']);
        $this->assertNull($result['conta_id']);
    }

    public function testCriarMarksMetaAsCompletedWhenInitialAllocationHitsTarget(): void
    {
        $repo = Mockery::mock(MetaRepository::class);
        $planLimit = Mockery::mock(PlanLimitService::class);
        $progress = Mockery::mock(MetaProgressService::class);

        $planLimit->shouldReceive('assertCanCreateMeta')
            ->once()
            ->with(11);

        $repo->shouldReceive('createForUser')
            ->once()
            ->withArgs(function (int $userId, array $data): bool {
                return $userId === 11
                    && $data['valor_alvo'] === 500.0
                    && $data['valor_alocado'] === 500.0
                    && $data['status'] === Meta::STATUS_CONCLUIDA;
            })
            ->andReturn($this->makeMeta([
                'id' => 3,
                'titulo' => 'Viagem',
                'valor_alvo' => 500.0,
                'valor_alocado' => 500.0,
                'valor_aporte_manual' => 500.0,
                'status' => Meta::STATUS_CONCLUIDA,
            ]));

        $progress->shouldReceive('recalculateMeta')
            ->once()
            ->with(11, 3, true)
            ->andReturn($this->makeMeta([
                'id' => 3,
                'titulo' => 'Viagem',
                'valor_alvo' => 500.0,
                'valor_alocado' => 500.0,
                'valor_aporte_manual' => 500.0,
                'status' => Meta::STATUS_CONCLUIDA,
            ]));

        $service = new MetaService($repo, $planLimit, $progress);

        $result = $service->criar(11, [
            'titulo' => 'Viagem',
            'valor_alvo' => 500.0,
            'valor_alocado' => 500.0,
        ]);

        $this->assertSame(Meta::STATUS_CONCLUIDA, $result['status']);
    }

    public function testAtualizarDelegatesManualAllocationSyncWhenProvided(): void
    {
        $repo = Mockery::mock(MetaRepository::class);
        $planLimit = Mockery::mock(PlanLimitService::class);
        $progress = Mockery::mock(MetaProgressService::class);

        $meta = Mockery::mock(Meta::class)->makePartial();
        $meta->id = 13;
        $meta->shouldReceive('update')
            ->once()
            ->with(['titulo' => 'Reserva revisada']);

        $repo->shouldReceive('findByIdAndUser')
            ->once()
            ->with(13, 77)
            ->andReturn($meta);

        $progress->shouldReceive('syncManualAllocationToTarget')
            ->once()
            ->with(77, 13, 350.0)
            ->andReturn($this->makeMeta([
                'id' => 13,
                'titulo' => 'Reserva revisada',
                'valor_alvo' => 1000.0,
                'valor_alocado' => 350.0,
                'valor_aporte_manual' => 350.0,
                'status' => Meta::STATUS_ATIVA,
            ]));

        $service = new MetaService($repo, $planLimit, $progress);

        $result = $service->atualizar(77, 13, [
            'titulo' => 'Reserva revisada',
            'valor_alocado' => 350.0,
        ]);

        $this->assertSame('Reserva revisada', $result['titulo']);
        $this->assertSame(350.0, $result['valor_alocado']);
    }

    public function testAdicionarAporteUsesProgressService(): void
    {
        $repo = Mockery::mock(MetaRepository::class);
        $planLimit = Mockery::mock(PlanLimitService::class);
        $progress = Mockery::mock(MetaProgressService::class);

        $progress->shouldReceive('incrementManualAllocation')
            ->once()
            ->with(77, 8, 125.0)
            ->andReturn($this->makeMeta([
                'id' => 8,
                'titulo' => 'Curso',
                'valor_alvo' => 800.0,
                'valor_alocado' => 325.0,
                'valor_aporte_manual' => 325.0,
                'status' => Meta::STATUS_ATIVA,
            ]));

        $service = new MetaService($repo, $planLimit, $progress);

        $result = $service->adicionarAporte(77, 8, 125.0);

        $this->assertSame(325.0, $result['valor_alocado']);
        $this->assertSame(Meta::STATUS_ATIVA, $result['status']);
    }

    public function testResumoUsesAllocatedValueAsProgressBase(): void
    {
        $repo = Mockery::mock(MetaRepository::class);
        $planLimit = Mockery::mock(PlanLimitService::class);
        $progress = Mockery::mock(MetaProgressService::class);

        $meta1 = $this->makeMeta([
            'id' => 1,
            'titulo' => 'Reserva',
            'valor_alvo' => 1000.0,
            'valor_alocado' => 400.0,
            'status' => Meta::STATUS_ATIVA,
        ]);
        $meta2 = $this->makeMeta([
            'id' => 2,
            'titulo' => 'Viagem',
            'valor_alvo' => 500.0,
            'valor_alocado' => 450.0,
            'status' => Meta::STATUS_ATIVA,
        ]);

        $repo->shouldReceive('findByUser')
            ->once()
            ->with(77)
            ->andReturn(new EloquentCollection([$meta1, $meta2]));

        $repo->shouldReceive('findByUser')
            ->once()
            ->with(77, Meta::STATUS_ATIVA)
            ->andReturn(new EloquentCollection([$meta1, $meta2]));

        $progress->shouldReceive('recalculateMeta')
            ->once()
            ->with(77, 1);
        $progress->shouldReceive('recalculateMeta')
            ->once()
            ->with(77, 2);

        $service = new MetaService($repo, $planLimit, $progress);

        $summary = $service->resumo(77);

        $this->assertSame(2, $summary['total_metas']);
        $this->assertSame(1500.0, $summary['total_alvo']);
        $this->assertSame(850.0, $summary['total_atual']);
        $this->assertSame(56.7, $summary['progresso_geral']);
        $this->assertSame('Viagem', $summary['proxima_concluir']['titulo']);
    }

    private function makeMeta(array $attributes): Meta
    {
        $payload = array_merge([
            'id' => 1,
            'titulo' => 'Meta',
            'descricao' => null,
            'tipo' => Meta::TIPO_ECONOMIA,
            'valor_alvo' => 1000.0,
            'valor_alocado' => 0.0,
            'valor_aporte_manual' => 0.0,
            'data_inicio' => '2026-01-01',
            'data_prazo' => null,
            'icone' => 'target',
            'cor' => '#6366f1',
            'conta_id' => null,
            'prioridade' => Meta::PRIORIDADE_MEDIA,
            'status' => Meta::STATUS_ATIVA,
        ], $attributes);

        $payload['valor_atual'] = $payload['valor_alocado'];

        $meta = new Meta();
        $meta->forceFill($payload);

        $meta->exists = true;

        return $meta;
    }
}
