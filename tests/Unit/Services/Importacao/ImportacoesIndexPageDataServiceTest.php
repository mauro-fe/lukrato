<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Importacao;

use Application\Repositories\ContaRepository;
use Application\Services\Importacao\ImportHistoryService;
use Application\Services\Importacao\ImportacoesIndexPageDataService;
use Application\Services\Importacao\ImportProfileConfigService;
use Application\Services\Plan\PlanLimitService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class ImportacoesIndexPageDataServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testBuildForUserUsesRichFreePlanFallbackWhenPlanSummaryFails(): void
    {
        $contaRepository = Mockery::mock(ContaRepository::class);
        $contaRepository->shouldReceive('findActive')->once()->with(42)->andReturn(new EloquentCollection());

        $profileConfigService = Mockery::mock(ImportProfileConfigService::class);
        $profileConfigService->shouldNotReceive('getForUserAndConta');

        $historyService = Mockery::mock(ImportHistoryService::class);
        $historyService
            ->shouldReceive('listForUser')
            ->once()
            ->with(42, ['conta_id' => 0, 'import_target' => 'conta'], 5)
            ->andReturn([]);

        $planLimitService = Mockery::mock(PlanLimitService::class);
        $planLimitService->shouldReceive('getLimitsSummary')->once()->with(42)->andThrow(new \RuntimeException('boom'));
        $planLimitService->shouldReceive('getConfig')->once()->andReturn([
            'limits' => [
                'free' => [
                    'import_conta_ofx' => 2,
                    'import_conta_csv' => 1,
                    'import_cartao_ofx' => 1,
                ],
            ],
        ]);
        $planLimitService->shouldReceive('canUseImportacao')->once()->with(42, 'ofx', 'conta')->andThrow(new \RuntimeException('quota-fallback'));

        $service = new ImportacoesIndexPageDataService(
            $contaRepository,
            $profileConfigService,
            $historyService,
            $planLimitService,
        );

        $result = $service->buildForUser(42);

        $this->assertSame('free', $result['planLimits']['plan']);
        $this->assertFalse($result['planLimits']['is_pro']);
        $this->assertFalse($result['planLimits']['is_ultra']);
        $this->assertSame('FREE', $result['planLimits']['plan_label']);
        $this->assertSame('pro', $result['planLimits']['upgrade_target']);
        $this->assertSame(2, $result['planLimits']['importacoes']['import_conta_ofx']['remaining']);
        $this->assertSame('import_conta_ofx', $result['importQuota']['bucket']);
        $this->assertSame('ofx', $result['importQuota']['source_type']);
        $this->assertSame('conta', $result['importQuota']['import_target']);
    }
}
