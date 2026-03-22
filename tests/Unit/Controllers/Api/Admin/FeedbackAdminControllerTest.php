<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api\Admin;

use Application\Controllers\Api\Admin\FeedbackAdminController;
use Application\Core\Exceptions\AuthException;
use Application\Models\Usuario;
use Application\Services\Feedback\FeedbackService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tests\Support\SessionIsolation;

class FeedbackAdminControllerTest extends TestCase
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

    public function testStatsReturnsSuccessResponse(): void
    {
        $this->seedAuthenticatedUserSession(91, 'Feedback Admin');

        $service = Mockery::mock(FeedbackService::class);
        $service
            ->shouldReceive('getStatsByTipo')
            ->once()
            ->andReturn(['nps' => 10]);
        $service
            ->shouldReceive('getNpsScore')
            ->once()
            ->with(null, null)
            ->andReturn(['score' => 80]);

        $controller = new FeedbackAdminController($service);

        $response = $controller->stats();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'success' => true,
            'message' => 'Success',
            'data' => [
                'by_tipo' => ['nps' => 10],
                'nps' => ['score' => 80],
            ],
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testExportThrowsAuthExceptionWhenSessionIsMissing(): void
    {
        $controller = new FeedbackAdminController(Mockery::mock(FeedbackService::class));

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Nao autenticado');

        $controller->export();
    }

    private function seedAuthenticatedUserSession(int $userId, string $name): void
    {
        $this->startIsolatedSession('feedback-admin-controller-test');

        $user = new Usuario();
        $user->id = $userId;
        $user->nome = $name;
        $user->is_admin = 1;

        $_SESSION['usuario_logged_in'] = true;
        $_SESSION['user_id'] = $userId;
        $_SESSION['usuario_nome'] = $name;
        $_SESSION['usuario_cache'] = [
            'id' => $userId,
            'data' => $user,
        ];
    }
}
