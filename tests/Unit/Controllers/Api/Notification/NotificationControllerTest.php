<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api\Notification;

use Application\Controllers\Api\Notification\NotificationController;
use Application\Core\Exceptions\AuthException;
use Application\Models\Usuario;
use Application\Services\Communication\NotificationService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tests\Support\SessionIsolation;

class NotificationControllerTest extends TestCase
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

    public function testCountReturnsSuccessResponse(): void
    {
        $this->seedAuthenticatedUserSession(9, 'Notification User');

        $service = Mockery::mock(NotificationService::class);
        $service
            ->shouldReceive('getUnreadCount')
            ->once()
            ->with(9)
            ->andReturn(4);

        $controller = new NotificationController($service);

        $response = $controller->count();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'success' => true,
            'message' => 'Success',
            'data' => ['unread_count' => 4],
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testMarkAsReadReturnsNotFoundResponseWhenNotificationDoesNotExist(): void
    {
        $this->seedAuthenticatedUserSession(10, 'Notification Missing');

        $service = Mockery::mock(NotificationService::class);
        $service
            ->shouldReceive('markAsRead')
            ->once()
            ->with(55, 10)
            ->andReturnFalse();

        $controller = new NotificationController($service);

        $response = $controller->markAsRead(55);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'Notificação não encontrada',
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testIndexThrowsAuthExceptionWhenSessionIsMissing(): void
    {
        $controller = new NotificationController(Mockery::mock(NotificationService::class));

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Não autenticado');

        $controller->index();
    }

    private function seedAuthenticatedUserSession(int $userId, string $name): void
    {
        $this->startIsolatedSession('notification-controller-test');

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
