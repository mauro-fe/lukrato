<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api\Dashboard;

use Application\Controllers\Api\Dashboard\OverviewController;
use Application\Core\Exceptions\AuthException;
use Application\Core\Response;
use Application\Models\Usuario;
use Application\Repositories\LancamentoRepository;
use Application\Repositories\MetaRepository;
use Application\Repositories\OrcamentoRepository;
use Application\Services\Dashboard\DashboardHealthSummaryService;
use Application\Services\Dashboard\DashboardInsightService;
use Application\Services\Dashboard\DashboardProvisaoService;
use Application\Services\Dashboard\HealthScoreInsightService;
use Application\Services\Dashboard\HealthScoreService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tests\Support\SessionIsolation;

class OverviewControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use SessionIsolation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetSessionState();
        $_GET = [];
    }

    protected function tearDown(): void
    {
        $_GET = [];
        $this->resetSessionState();
        parent::tearDown();
    }

    public function testComparativoCompetenciaCaixaReturnsResponseObject(): void
    {
        $this->seedAuthenticatedUserSession(123, 'Dashboard User');
        $_GET['month'] = '2026-03';

        $lancamentoRepo = Mockery::mock(LancamentoRepository::class);
        $provisaoService = Mockery::mock(DashboardProvisaoService::class);
        $orcamentoRepo = Mockery::mock(OrcamentoRepository::class);
        $metaRepo = Mockery::mock(MetaRepository::class);
        $healthScoreService = Mockery::mock(HealthScoreService::class);
        $dashboardInsightService = Mockery::mock(DashboardInsightService::class);
        $healthScoreInsightService = Mockery::mock(HealthScoreInsightService::class);
        $dashboardHealthSummaryService = Mockery::mock(DashboardHealthSummaryService::class);

        $comparativo = ['receitas' => 1500.0, 'despesas' => 900.0];
        $payload = ['competencia' => 600, 'caixa' => 580];

        $lancamentoRepo
            ->shouldReceive('getResumoCompetenciaVsCaixa')
            ->once()
            ->with(123, '2026-03')
            ->andReturn($comparativo);

        $dashboardInsightService
            ->shouldReceive('buildComparativoCompetenciaCaixaResponse')
            ->once()
            ->with($comparativo, '2026-03')
            ->andReturn($payload);

        $controller = new OverviewController(
            $lancamentoRepo,
            $provisaoService,
            $orcamentoRepo,
            $metaRepo,
            $healthScoreService,
            $dashboardInsightService,
            $healthScoreInsightService,
            $dashboardHealthSummaryService,
        );

        $response = $controller->comparativoCompetenciaCaixa();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'success' => true,
            'message' => 'Success',
            'data' => $payload,
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testOverviewThrowsAuthExceptionWhenSessionIsMissing(): void
    {
        $controller = new OverviewController();

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Não autenticado');

        $controller->overview();
    }

    private function seedAuthenticatedUserSession(int $userId, string $name): void
    {
        $this->startIsolatedSession('dashboard-overview-controller-test');

        $user = new Usuario();
        $user->id = $userId;
        $user->nome = $name;
        $user->is_admin = 0;

        $_SESSION['usuario_logged_in'] = true;
        $_SESSION['user_id'] = $userId;
        $_SESSION['usuario_nome'] = $name;
        $_SESSION['usuario_cache'] = [
            'id' => $userId,
            'data' => $user,
        ];
    }
}
