<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api\Report;

use Application\Builders\ReportExportBuilder;
use Application\Controllers\Api\Report\RelatoriosController;
use Application\Core\Exceptions\AuthException;
use Application\DTO\ReportParameters;
use Application\Enums\ReportType;
use Application\Models\Usuario;
use Application\Services\Financeiro\ComparativesService;
use Application\Services\Financeiro\InsightsService;
use Application\Services\Report\ExcelExportService;
use Application\Services\Report\PdfExportService;
use Application\Services\Report\ReportApiWorkflowService;
use Application\Services\Report\ReportService;
use Carbon\Carbon;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tests\Support\SessionIsolation;

class RelatoriosControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use SessionIsolation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetSessionState();
        $_GET = [];
        $_POST = [];
        $_REQUEST = [];
    }

    protected function tearDown(): void
    {
        $_GET = [];
        $_POST = [];
        $_REQUEST = [];
        $this->resetSessionState();
        parent::tearDown();
    }

    public function testIndexReturnsSuccessResponse(): void
    {
        $this->seedAuthenticatedSession(1401, 'Report User', false, true);
        $_GET['month'] = '2026-03';

        $workflowService = Mockery::mock(ReportApiWorkflowService::class);
        $workflowService
            ->shouldReceive('generateReport')
            ->once()
            ->with(
                1401,
                Mockery::type(TestRelatoriosUser::class),
                Mockery::on(static fn(array $query): bool => ($query['month'] ?? null) === '2026-03')
            )
            ->andReturn([
                'result' => [
                    'labels' => ['Moradia'],
                    'series' => [500.0],
                ],
                'type' => ReportType::DESPESAS_POR_CATEGORIA,
                'params' => new ReportParameters(
                    start: Carbon::parse('2026-03-01'),
                    end: Carbon::parse('2026-03-31'),
                    accountId: null,
                    userId: 1401,
                    includeTransfers: false
                ),
            ]);

        $controller = new RelatoriosController(
            Mockery::mock(ReportService::class),
            Mockery::mock(ReportExportBuilder::class),
            Mockery::mock(PdfExportService::class),
            Mockery::mock(ExcelExportService::class),
            Mockery::mock(InsightsService::class),
            Mockery::mock(ComparativesService::class),
            $workflowService,
        );

        $response = $controller->index();
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($payload['success']);
        $this->assertSame('Success', $payload['message']);
        $this->assertSame(['Moradia'], $payload['data']['labels']);
        $this->assertSame([500], $payload['data']['series']);
        $this->assertSame('despesas_por_categoria', $payload['data']['type']);
        $this->assertSame('2026-03-01', $payload['data']['start']);
        $this->assertSame('2026-03-31', $payload['data']['end']);
    }

    public function testExportReturnsForbiddenForNonProUser(): void
    {
        $this->seedAuthenticatedSession(1402, 'Report Free User', false, true);

        $controller = new RelatoriosController(
            Mockery::mock(ReportService::class),
            Mockery::mock(ReportExportBuilder::class),
            Mockery::mock(PdfExportService::class),
            Mockery::mock(ExcelExportService::class),
            Mockery::mock(InsightsService::class),
            Mockery::mock(ComparativesService::class),
        );

        $response = $controller->export();

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'Exportação de relatórios é um recurso exclusivo do plano PRO.',
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testExportReturnsBinaryResponseForProUser(): void
    {
        $this->seedAuthenticatedSession(1403, 'Report Pro User', true, true);
        $_GET['month'] = '2026-03';

        $workflowService = Mockery::mock(ReportApiWorkflowService::class);
        $workflowService
            ->shouldReceive('exportReport')
            ->once()
            ->with(
                1403,
                Mockery::type(TestRelatoriosUser::class),
                Mockery::on(static fn(array $query): bool => ($query['month'] ?? null) === '2026-03')
            )
            ->andReturn([
                'content' => 'PDFDATA',
                'filename' => 'relatorio_2026_03.pdf',
                'mime' => 'application/pdf',
            ]);

        $controller = new RelatoriosController(
            Mockery::mock(ReportService::class),
            Mockery::mock(ReportExportBuilder::class),
            Mockery::mock(PdfExportService::class),
            Mockery::mock(ExcelExportService::class),
            Mockery::mock(InsightsService::class),
            Mockery::mock(ComparativesService::class),
            $workflowService,
        );

        $response = $controller->export();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('PDFDATA', $response->getContent());
        $this->assertSame('application/pdf', $response->getHeaders()['Content-Type']);
        $this->assertStringEndsWith('.pdf"', $response->getHeaders()['Content-Disposition']);
    }

    public function testIndexThrowsAuthExceptionWhenSessionIsMissing(): void
    {
        $controller = new RelatoriosController(
            Mockery::mock(ReportService::class),
            Mockery::mock(ReportExportBuilder::class),
            Mockery::mock(PdfExportService::class),
            Mockery::mock(ExcelExportService::class),
            Mockery::mock(InsightsService::class),
            Mockery::mock(ComparativesService::class),
        );

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Nao autenticado');

        $controller->index();
    }

    private function seedAuthenticatedSession(int $userId, string $name, bool $isPro, bool $canAccessReports): void
    {
        $this->startIsolatedSession('relatorios-controller-test');

        $user = new TestRelatoriosUser();
        $user->id = $userId;
        $user->nome = $name;
        $user->is_admin = 0;
        $user->senha = password_hash('Senha@123', PASSWORD_DEFAULT);
        $user->pro = $isPro;
        $user->reportsEnabled = $canAccessReports;

        $_SESSION['usuario_logged_in'] = true;
        $_SESSION['user_id'] = $userId;
        $_SESSION['usuario_nome'] = $name;
        $_SESSION['usuario_cache'] = [
            'id' => $userId,
            'data' => $user,
        ];
    }
}

final class TestRelatoriosUser extends Usuario
{
    public bool $pro = false;
    public bool $reportsEnabled = true;

    public function isPro(): bool
    {
        return $this->pro;
    }

    public function podeAcessar(string $feature): bool
    {
        return $feature === 'reports' ? $this->reportsEnabled : true;
    }
}
