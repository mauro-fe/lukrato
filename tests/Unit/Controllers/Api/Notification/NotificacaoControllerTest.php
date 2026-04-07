<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api\Notification;

use Application\Controllers\Api\Notification\NotificacaoController;
use Application\Core\Exceptions\AuthException;
use Application\Models\Usuario;
use Application\Services\Cartao\CartaoCreditoService;
use Application\Services\Cartao\CartaoFaturaService;
use Application\Services\Communication\NotificationInboxService;
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

        $legacyQuery = Mockery::mock();
        $legacyQuery->shouldReceive('where')->once()->with('lida', false)->andReturnSelf();
        $legacyQuery->shouldReceive('count')->once()->andReturn(2);

        $legacyModel = Mockery::mock('alias:Application\Models\Notificacao');
        $legacyModel->shouldReceive('where')->once()->with('user_id', 1501)->andReturn($legacyQuery);

        $campaignQuery = Mockery::mock();
        $campaignQuery->shouldReceive('where')->once()->with('is_read', false)->andReturnSelf();
        $campaignQuery->shouldReceive('count')->once()->andReturn(3);

        $campaignModel = Mockery::mock('alias:Application\Models\Notification');
        $campaignModel->shouldReceive('where')->once()->with('user_id', 1501)->andReturn($campaignQuery);

        $cartaoService = Mockery::mock(CartaoCreditoService::class);
        $cartaoService->shouldReceive('verificarLimitesBaixos')->once()->with(1501)->andReturn([]);

        $faturaService = Mockery::mock(CartaoFaturaService::class);
        $faturaService->shouldReceive('verificarVencimentosProximos')->once()->with(1501)->andReturn([]);

        $controller = new NotificacaoController($cartaoService, $faturaService);

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

        $controller = new NotificacaoController(
            Mockery::mock(CartaoCreditoService::class),
            Mockery::mock(CartaoFaturaService::class),
        );

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
        $controller = new NotificacaoController(
            Mockery::mock(CartaoCreditoService::class),
            Mockery::mock(CartaoFaturaService::class),
        );

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Não autenticado');

        $controller->marcarLida();
    }

    public function testIndexDelegatesToInboxServiceAndPersistsIgnoredAlerts(): void
    {
        $this->seedAuthenticatedSession(1503, 'Notification User');

        $service = Mockery::mock(NotificationInboxService::class);
        $service->shouldReceive('getInbox')
            ->once()
            ->with(1503, [])
            ->andReturn([
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
                'ignored_alerts' => ['cartao_venc_7' => 1234567890],
            ]);

        $controller = new NotificacaoController(
            Mockery::mock(CartaoCreditoService::class),
            Mockery::mock(CartaoFaturaService::class),
            $service
        );

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
