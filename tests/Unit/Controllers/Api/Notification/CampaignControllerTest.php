<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api\Notification;

use Application\Controllers\Api\Notification\CampaignController;
use Application\Core\Exceptions\AuthException;
use Application\Models\Usuario;
use Application\Services\Communication\CampaignApiWorkflowService;
use Application\Services\Communication\NotificationService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tests\Support\SessionIsolation;

class CampaignControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use SessionIsolation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetSessionState();
        $_POST = [];
        $_GET = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    protected function tearDown(): void
    {
        $_POST = [];
        $_GET = [];
        unset($_SERVER['REQUEST_METHOD']);
        $this->resetSessionState();
        parent::tearDown();
    }

    public function testIndexReturnsSuccessResponse(): void
    {
        $this->seedAdminSession(901, 'Campaign Admin');
        $_GET['page'] = '3';
        $_GET['per_page'] = '25';

        $notificationService = Mockery::mock(NotificationService::class);
        $notificationService
            ->shouldReceive('listCampaigns')
            ->once()
            ->with(3, 25)
            ->andReturn([
                'items' => [],
                'pagination' => ['page' => 3],
            ]);

        $controller = new CampaignController($notificationService);

        $response = $controller->index();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'success' => true,
            'message' => 'Campanhas listadas com sucesso',
            'data' => [
                'items' => [],
                'pagination' => ['page' => 3],
            ],
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testStoreReturnsBadRequestWhenTitleIsMissing(): void
    {
        $this->seedAdminSession(902, 'Campaign Admin');
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'message' => 'Mensagem vÃ¡lida',
        ];

        $controller = new CampaignController(Mockery::mock(NotificationService::class));

        $response = $controller->store();

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'O tÃ­tulo Ã© obrigatÃ³rio',
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testOptionsReturnsSuccessResponse(): void
    {
        $this->seedAdminSession(903, 'Campaign Admin');

        $controller = new CampaignController(Mockery::mock(NotificationService::class));

        $response = $controller->options();
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($payload['success']);
        $this->assertSame('OpÃ§Ãµes obtidas com sucesso', $payload['message']);
        $this->assertArrayHasKey('types', $payload['data']);
        $this->assertArrayHasKey('plans', $payload['data']);
        $this->assertArrayHasKey('statuses', $payload['data']);
        $this->assertArrayHasKey('inactive_days', $payload['data']);
    }

    public function testProcessDueReturnsSuccessResponse(): void
    {
        $this->seedAdminSession(904, 'Campaign Admin');
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $workflow = Mockery::mock(CampaignApiWorkflowService::class);
        $workflow
            ->shouldReceive('processDueCampaigns')
            ->once()
            ->andReturn([
                'processed' => 2,
                'sent' => 1,
                'partial' => 0,
                'failed' => 1,
                'stuck_recovered' => 0,
            ]);

        $controller = new CampaignController(Mockery::mock(NotificationService::class), $workflow);
        $response = $controller->processDue();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'success' => true,
            'message' => 'Fila de campanhas sincronizada com sucesso',
            'data' => [
                'processed' => 2,
                'sent' => 1,
                'partial' => 0,
                'failed' => 1,
                'stuck_recovered' => 0,
            ],
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testIndexThrowsAuthExceptionWhenSessionIsMissing(): void
    {
        $controller = new CampaignController(Mockery::mock(NotificationService::class));

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Não autenticado');

        $controller->index();
    }

    private function seedAdminSession(int $userId, string $name): void
    {
        $this->startIsolatedSession('campaign-controller-test');

        $user = new Usuario();
        $user->id = $userId;
        $user->nome = $name;
        $user->is_admin = 1;
        $user->senha = password_hash('Senha@123', PASSWORD_DEFAULT);

        $_SESSION['usuario_logged_in'] = true;
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['user_id'] = $userId;
        $_SESSION['admin_id'] = $userId;
        $_SESSION['usuario_nome'] = $name;
        $_SESSION['usuario_cache'] = [
            'id' => $userId,
            'data' => $user,
        ];
    }
}
