<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api\Financeiro;

use Application\Controllers\Api\Financeiro\DashboardController;
use Application\Core\Exceptions\AuthException;
use Application\Core\Response;
use Application\Repositories\LancamentoRepository;
use Application\Repositories\MetaRepository;
use Application\Repositories\OrcamentoRepository;
use Application\Services\Financeiro\DashboardHealthSummaryService;
use Application\Services\Financeiro\DashboardInsightService;
use Application\Services\Financeiro\DashboardProvisaoService;
use Application\Services\Financeiro\HealthScoreInsightService;
use Application\Services\Financeiro\HealthScoreService;
use Application\Models\Usuario;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tests\Support\SessionIsolation;

class DashboardControllerTest extends TestCase
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

        $controller = new DashboardController(
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

    public function testComparativoCompetenciaCaixaThrowsAuthExceptionWhenSessionIsMissing(): void
    {
        $controller = $this->buildController();

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Nao autenticado');

        $controller->comparativoCompetenciaCaixa();
    }

    public function testHealthSummaryReturnsErrorResponseWhenServiceFails(): void
    {
        $this->seedAuthenticatedUserSession(321, 'Dashboard Summary');
        $currentMonth = (new \DateTimeImmutable('first day of this month'))->format('Y-m');

        $dashboardHealthSummaryService = Mockery::mock(DashboardHealthSummaryService::class);
        $dashboardHealthSummaryService
            ->shouldReceive('generate')
            ->once()
            ->with(321, $currentMonth)
            ->andThrow(new \RuntimeException('boom'));

        $controller = $this->buildController(
            dashboardHealthSummaryService: $dashboardHealthSummaryService,
        );

        $response = $controller->healthSummary();
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertFalse($payload['success']);
        $this->assertSame('Erro ao gerar resumo.', $payload['message']);
        $this->assertArrayHasKey('error_id', $payload);
        $this->assertArrayHasKey('request_id', $payload);
    }

    private function buildController(
        ?LancamentoRepository $lancamentoRepo = null,
        ?DashboardProvisaoService $provisaoService = null,
        ?OrcamentoRepository $orcamentoRepo = null,
        ?MetaRepository $metaRepo = null,
        ?HealthScoreService $healthScoreService = null,
        ?DashboardInsightService $dashboardInsightService = null,
        ?HealthScoreInsightService $healthScoreInsightService = null,
        ?DashboardHealthSummaryService $dashboardHealthSummaryService = null,
    ): DashboardController {
        return new DashboardController(
            $lancamentoRepo ?? Mockery::mock(LancamentoRepository::class),
            $provisaoService ?? Mockery::mock(DashboardProvisaoService::class),
            $orcamentoRepo ?? Mockery::mock(OrcamentoRepository::class),
            $metaRepo ?? Mockery::mock(MetaRepository::class),
            $healthScoreService ?? Mockery::mock(HealthScoreService::class),
            $dashboardInsightService ?? Mockery::mock(DashboardInsightService::class),
            $healthScoreInsightService ?? Mockery::mock(HealthScoreInsightService::class),
            $dashboardHealthSummaryService ?? Mockery::mock(DashboardHealthSummaryService::class),
        );
    }

    private function seedAuthenticatedUserSession(int $userId, string $name): void
    {
        $this->startIsolatedSession('dashboard-controller-test');

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
