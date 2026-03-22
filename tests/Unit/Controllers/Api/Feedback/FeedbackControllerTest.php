<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api\Feedback;

use Application\Controllers\Api\Feedback\FeedbackController;
use Application\Core\Exceptions\AuthException;
use Application\Core\Response;
use Application\Models\Usuario;
use Application\Services\Feedback\FeedbackService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tests\Support\SessionIsolation;

class FeedbackControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use SessionIsolation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetSessionState();
        $_GET = [];
        $_SERVER['HTTP_REFERER'] = '/dashboard';
    }

    protected function tearDown(): void
    {
        unset($_SERVER['HTTP_REFERER']);
        $_GET = [];
        $this->resetSessionState();
        parent::tearDown();
    }

    public function testStoreReturnsSuccessResponse(): void
    {
        $this->seedAuthenticatedUserSession(7, 'Feedback User');

        $service = Mockery::mock(FeedbackService::class);
        $service
            ->shouldReceive('store')
            ->once()
            ->with(7, ['pagina' => '/dashboard'])
            ->andReturn([
                'success' => true,
                'data' => ['id' => 99],
            ]);

        $controller = new FeedbackController($service);

        $response = $controller->store();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'success' => true,
            'message' => 'Feedback registrado com sucesso.',
            'data' => ['id' => 99],
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testCanMicroReturnsErrorResponseWhenContextIsMissing(): void
    {
        $this->seedAuthenticatedUserSession(8, 'Feedback Context');

        $controller = new FeedbackController(Mockery::mock(FeedbackService::class));

        $response = $controller->canMicro();

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'Contexto obrigatorio.',
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testCheckNpsThrowsAuthExceptionWhenSessionIsMissing(): void
    {
        $controller = new FeedbackController(Mockery::mock(FeedbackService::class));

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Nao autenticado');

        $controller->checkNps();
    }

    private function seedAuthenticatedUserSession(int $userId, string $name): void
    {
        $this->startIsolatedSession('feedback-controller-test');

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
