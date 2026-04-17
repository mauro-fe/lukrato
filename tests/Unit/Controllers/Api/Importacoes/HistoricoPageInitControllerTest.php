<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api\Importacoes;

use Application\Controllers\Api\Importacoes\HistoricoPageInitController;
use Application\Models\Usuario;
use Application\Services\Importacao\ImportacoesHistoricoPageDataService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tests\Support\SessionIsolation;

class HistoricoPageInitControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use SessionIsolation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetSessionState();
        $_GET = [];
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_ACCEPT'] = 'application/json';
    }

    protected function tearDown(): void
    {
        $_GET = [];
        $_POST = [];
        unset($_SERVER['REQUEST_METHOD'], $_SERVER['HTTP_ACCEPT']);
        $this->resetSessionState();
        parent::tearDown();
    }

    public function testReturnsHistoricoPageInitializationPayloadForAuthenticatedUser(): void
    {
        $this->seedAuthenticatedUserSession(1503, 'Import History User');

        $_GET = [
            'conta_id' => 12,
            'source_type' => 'csv',
            'status' => 'processed',
            'import_target' => 'conta',
        ];

        $service = Mockery::mock(ImportacoesHistoricoPageDataService::class);
        $service
            ->shouldReceive('buildForUser')
            ->once()
            ->with(1503, [
                'conta_id' => 12,
                'source_type' => 'csv',
                'status' => 'processed',
                'import_target' => 'conta',
            ])
            ->andReturn([
                'accounts' => [],
                'selectedAccountId' => 12,
                'selectedSourceType' => 'csv',
                'selectedImportTarget' => 'conta',
                'selectedStatus' => 'processed',
                'statusOptions' => ['processed'],
                'historyItems' => [],
                'totals' => [
                    'batches' => 0,
                    'totalRows' => 0,
                    'importedRows' => 0,
                    'duplicateRows' => 0,
                    'errorRows' => 0,
                ],
            ]);

        $controller = new HistoricoPageInitController($service);
        $response = $controller->__invoke();
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue((bool) ($payload['success'] ?? false));
        $this->assertSame(12, (int) ($payload['data']['selectedAccountId'] ?? 0));
        $this->assertSame('csv', $payload['data']['selectedSourceType'] ?? null);
        $this->assertSame('processed', $payload['data']['selectedStatus'] ?? null);
    }

    private function seedAuthenticatedUserSession(int $userId, string $name): void
    {
        $this->startIsolatedSession('history-page-init-controller-test');

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
