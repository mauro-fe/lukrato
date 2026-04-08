<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api\Notification;

use Application\Controllers\Api\Notification\NotificacaoController;
use Application\Core\Exceptions\AuthException;
use Application\Models\Usuario;
use Application\Services\Communication\NotificationApiWorkflowService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use Tests\Support\SessionIsolation;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class NotificacaoControllerTest extends TestCase
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

    public function testUnreadCountReturnsCombinedCounts(): void
    {
        $this->seedAuthenticatedSession(1501, 'Notification User');

        $workflowService = Mockery::mock(NotificationApiWorkflowService::class);
        $workflowService
            ->shouldReceive('unreadCount')
            ->once()
            ->with(1501, [])
            ->andReturn([
                'success' => true,
                'data' => ['unread' => 5],
                'ignored_alerts' => [],
            ]);

        $controller = new NotificacaoController($workflowService);

        $response = $controller->unreadCount();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'success' => true,
            'message' => 'Success',
            'data' => ['unread' => 5],
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testMarcarLidaReturnsValidationErrorWhenIdsAreMissing(): void
    {
        $this->seedAuthenticatedSession(1502, 'Notification User');

        $workflowService = Mockery::mock(NotificationApiWorkflowService::class);
        $workflowService
            ->shouldReceive('markAsRead')
            ->once()
            ->with(1502, [], [])
            ->andReturn([
                'success' => false,
                'status' => 422,
                'message' => 'Validation failed',
                'errors' => [
                    'ids' => 'Nenhum ID de notificacao valido fornecido.',
                ],
            ]);

        $controller = new NotificacaoController($workflowService);

        $response = $controller->marcarLida();

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => [
                'ids' => 'Nenhum ID de notificacao valido fornecido.',
            ],
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testUnreadCountThrowsAuthExceptionWhenSessionIsMissing(): void
    {
        $controller = new NotificacaoController(Mockery::mock(NotificationApiWorkflowService::class));

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Não autenticado');

        $controller->marcarLida();
    }

    public function testIndexDelegatesToInboxServiceAndPersistsIgnoredAlerts(): void
    {
        $this->seedAuthenticatedSession(1503, 'Notification User');

        $workflowService = Mockery::mock(NotificationApiWorkflowService::class);
        $workflowService->shouldReceive('inbox')
            ->once()
            ->with(1503, [])
            ->andReturn([
                'success' => true,
                'data' => [
                    'itens' => [
                        [
                            'id' => 'cartao_venc_7',
                            'tipo' => 'alerta',
                            'titulo' => 'Fatura vencendo',
                            'mensagem' => 'Teste',
                            'lida' => 0,
                            'created_at' => '2026-03-19 10:00:00',
                        ],
                    ],
                    'unread' => 1,
                ],
                'ignored_alerts' => ['cartao_venc_7' => 1234567890],
            ]);

        $controller = new NotificacaoController($workflowService);

        $response = $controller->index();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'success' => true,
            'message' => 'Success',
            'data' => [
                'itens' => [
                    [
                        'id' => 'cartao_venc_7',
                        'tipo' => 'alerta',
                        'titulo' => 'Fatura vencendo',
                        'mensagem' => 'Teste',
                        'lida' => 0,
                        'created_at' => '2026-03-19 10:00:00',
                    ],
                ],
                'unread' => 1,
            ],
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
        $this->assertSame(['cartao_venc_7' => 1234567890], $_SESSION['alertas_ignorados']);
    }

    private function seedAuthenticatedSession(int $userId, string $name): void
    {
        $this->startIsolatedSession('notificacao-controller-test');

        $user = new Usuario();
        $user->id = $userId;
        $user->nome = $name;
        $user->is_admin = 0;
        $user->senha = password_hash('Senha@123', PASSWORD_DEFAULT);

        $_SESSION['usuario_logged_in'] = true;
        $_SESSION['user_id'] = $userId;
        $_SESSION['usuario_nome'] = $name;
        $_SESSION['usuario_cache'] = [
            'id' => $userId,
            'data' => $user,
        ];
    }
}
