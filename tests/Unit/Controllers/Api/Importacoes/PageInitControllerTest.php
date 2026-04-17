<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api\Importacoes;

use Application\Controllers\Api\Importacoes\PageInitController;
use Application\Models\Usuario;
use Application\Services\Importacao\ImportacoesIndexPageDataService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tests\Support\SessionIsolation;

class PageInitControllerTest extends TestCase
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

    public function testReturnsPageInitializationPayloadForAuthenticatedUser(): void
    {
        $this->seedAuthenticatedUserSession(1501, 'Import Init User');

        $_GET = [
            'import_target' => 'cartao',
            'conta_id' => 18,
            'cartao_id' => 44,
            'source_type' => 'csv',
        ];

        $service = Mockery::mock(ImportacoesIndexPageDataService::class);
        $service
            ->shouldReceive('buildForUser')
            ->once()
            ->with(1501, [
                'import_target' => 'cartao',
                'conta_id' => 18,
                'cartao_id' => 44,
                'source_type' => 'csv',
            ])
            ->andReturn([
                'importTarget' => 'cartao',
                'sourceType' => 'csv',
                'selectedAccountId' => 18,
                'selectedCardId' => 44,
                'accounts' => [],
                'cards' => [],
                'latestHistoryItems' => [],
                'planLimits' => ['plan' => 'free'],
                'importQuota' => ['allowed' => true],
                'confirmAsyncDefault' => false,
            ]);

        $controller = new PageInitController($service);
        $response = $controller->__invoke();
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue((bool) ($payload['success'] ?? false));
        $this->assertSame('cartao', $payload['data']['importTarget'] ?? null);
        $this->assertSame('csv', $payload['data']['sourceType'] ?? null);
        $this->assertSame(18, (int) ($payload['data']['selectedAccountId'] ?? 0));
        $this->assertSame(44, (int) ($payload['data']['selectedCardId'] ?? 0));
    }

    private function seedAuthenticatedUserSession(int $userId, string $name): void
    {
        $this->startIsolatedSession('page-init-controller-test');

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
