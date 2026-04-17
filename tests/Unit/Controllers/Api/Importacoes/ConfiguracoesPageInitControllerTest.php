<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api\Importacoes;

use Application\Controllers\Api\Importacoes\ConfiguracoesPageInitController;
use Application\Models\Usuario;
use Application\Services\Importacao\ImportacoesConfiguracoesPageDataService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tests\Support\SessionIsolation;

class ConfiguracoesPageInitControllerTest extends TestCase
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

    public function testReturnsConfiguracoesPageInitializationPayloadForAuthenticatedUser(): void
    {
        $this->seedAuthenticatedUserSession(1502, 'Import Config User');

        $_GET = [
            'conta_id' => 18,
        ];

        $service = Mockery::mock(ImportacoesConfiguracoesPageDataService::class);
        $service
            ->shouldReceive('buildForUser')
            ->once()
            ->with(1502, [
                'conta_id' => 18,
            ])
            ->andReturn([
                'accounts' => [
                    ['id' => 18, 'nome' => 'Conta principal', 'instituicao' => 'Banco'],
                ],
                'selectedAccountId' => 18,
                'profileConfig' => [
                    'conta_id' => 18,
                    'source_type' => 'csv',
                ],
            ]);

        $controller = new ConfiguracoesPageInitController($service);
        $response = $controller->__invoke();
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue((bool) ($payload['success'] ?? false));
        $this->assertSame(18, (int) ($payload['data']['selectedAccountId'] ?? 0));
        $this->assertSame('csv', $payload['data']['profileConfig']['source_type'] ?? null);
    }

    private function seedAuthenticatedUserSession(int $userId, string $name): void
    {
        $this->startIsolatedSession('config-page-init-controller-test');

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
