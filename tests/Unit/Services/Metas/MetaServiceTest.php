<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Metas;

use Application\Models\Meta;
use Application\Repositories\MetaRepository;
use Application\Services\Metas\MetaProgressService;
use Application\Services\Metas\MetaService;
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

    public function testAtualizarRecalculatesAfterUpdatingAllowedFields(): void
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

        $progress->shouldReceive('recalculateMeta')
            ->once()
            ->with(77, 13)
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

    public function testAtualizarNormalizesValorAlvoBeforePersisting(): void
    {
        $repo = Mockery::mock(MetaRepository::class);
        $planLimit = Mockery::mock(PlanLimitService::class);
        $progress = Mockery::mock(MetaProgressService::class);

        $meta = Mockery::mock(Meta::class)->makePartial();
        $meta->id = 14;
        $meta->shouldReceive('update')
            ->once()
            ->with(['valor_alvo' => 999.46]);

        $repo->shouldReceive('findByIdAndUser')
            ->once()
            ->with(14, 77)
            ->andReturn($meta);

        $progress->shouldReceive('recalculateMeta')
            ->once()
            ->with(77, 14)
            ->andReturn($this->makeMeta([
                'id' => 14,
                'titulo' => 'Reserva revisada',
                'valor_alvo' => 999.46,
                'valor_alocado' => 300.0,
                'valor_aporte_manual' => 300.0,
                'status' => Meta::STATUS_ATIVA,
            ]));

        $service = new MetaService($repo, $planLimit, $progress);
        $result = $service->atualizar(77, 14, ['valor_alvo' => '999.456']);

        $this->assertSame(999.46, $result['valor_alvo']);
    }

    public function testAtualizarThrowsWhenValorAlvoIsInvalid(): void
    {
        $repo = Mockery::mock(MetaRepository::class);
        $planLimit = Mockery::mock(PlanLimitService::class);
        $progress = Mockery::mock(MetaProgressService::class);

        $meta = Mockery::mock(Meta::class)->makePartial();
        $meta->id = 19;
        $meta->shouldNotReceive('update');

        $repo->shouldReceive('findByIdAndUser')
            ->once()
            ->with(19, 77)
            ->andReturn($meta);

        $progress->shouldNotReceive('recalculateMeta');

        $service = new MetaService($repo, $planLimit, $progress);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('O valor da meta deve ser maior que zero.');

        $service->atualizar(77, 19, ['valor_alvo' => 0]);
    }

    public function testAdicionarAporteUsesProgressService(): void
    {
        $repo = Mockery::mock(MetaRepository::class);
        $planLimit = Mockery::mock(PlanLimitService::class);
        $progress = Mockery::mock(MetaProgressService::class);

        $service = new MetaService($repo, $planLimit, $progress);
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Aporte manual em meta foi descontinuado.');
        $service->adicionarAporte(77, 8, 125.0);
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


