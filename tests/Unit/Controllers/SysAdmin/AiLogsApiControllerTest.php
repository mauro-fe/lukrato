<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\SysAdmin;

use Application\Controllers\SysAdmin\AiLogsApiController;
use Application\Core\Exceptions\AuthException;
use Application\Models\Usuario;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use Tests\Support\SessionIsolation;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class AiLogsApiControllerTest extends TestCase
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
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    protected function tearDown(): void
    {
        $_GET = [];
        $_POST = [];
        $_REQUEST = [];
        unset($_SERVER['REQUEST_METHOD']);
        $this->resetSessionState();
        parent::tearDown();
    }

    public function testIndexThrowsAuthExceptionWhenSessionIsMissing(): void
    {
        $controller = new AiLogsApiController();

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Não autenticado');

        $controller->index();
    }

    public function testSummaryReturnsSuccessResponseForAdmin(): void
    {
        $this->seedAuthenticatedSession(2101, 'SysAdmin User', true);
        $_GET['hours'] = '48';

        $service = Mockery::mock('alias:Application\Services\AI\AiLogService');
        $service
            ->shouldReceive('summary')
            ->once()
            ->with(48)
            ->andReturn(['total' => 5]);

        $controller = new AiLogsApiController();

        $response = $controller->summary();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'success' => true,
            'message' => 'Success',
            'data' => ['total' => 5],
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    private function seedAuthenticatedSession(int $userId, string $name, bool $isAdmin): void
    {
        $this->startIsolatedSession('ai-logs-api-controller-test');

        $user = new Usuario();
        $user->id = $userId;
        $user->nome = $name;
        $user->is_admin = $isAdmin ? 1 : 0;

        $_SESSION['usuario_logged_in'] = true;
        $_SESSION['user_id'] = $userId;
        $_SESSION['usuario_nome'] = $name;
        $_SESSION['usuario_cache'] = [
            'id' => $userId,
            'data' => $user,
        ];
    }
}
