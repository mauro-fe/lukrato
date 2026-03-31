<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api\Dashboard;

use Application\Controllers\Api\Dashboard\HealthController;
use Application\Core\Exceptions\AuthException;
use Application\Models\Usuario;
use Application\Repositories\LancamentoRepository;
use Application\Repositories\MetaRepository;
use Application\Repositories\OrcamentoRepository;
use Application\Services\Financeiro\DashboardHealthSummaryService;
use Application\Services\Financeiro\DashboardInsightService;
use Application\Services\Financeiro\DashboardProvisaoService;
use Application\Services\Financeiro\HealthScoreInsightService;
use Application\Services\Financeiro\HealthScoreService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tests\Support\SessionIsolation;

class HealthControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use SessionIsolation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetSessionState();
    }

    protected function tearDown(): void
    {
        $this->resetSessionState();
        parent::tearDown();
    }

    public function testHealthScoreThrowsAuthExceptionWhenSessionIsMissing(): void
    {
        $controller = new HealthController();

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Nao autenticado');

        $controller->healthScore();
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
    ): HealthController {
        return new HealthController(
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
        $this->startIsolatedSession('dashboard-health-controller-test');

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
