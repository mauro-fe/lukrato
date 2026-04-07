<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api\Admin;

use Application\Controllers\Api\Admin\SysAdminController;
use Application\Core\Exceptions\AuthException;
use Application\Models\Usuario;
use Application\Services\Admin\SysAdminOpsService;
use PHPUnit\Framework\TestCase;
use Tests\Support\SessionIsolation;

class SysAdminControllerTest extends TestCase
{
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
        unset($_SERVER['REQUEST_METHOD']);
        $this->resetSessionState();
        parent::tearDown();
    }

    public function testMaintenanceStatusThrowsAuthExceptionWhenSessionIsMissing(): void
    {
        $controller = new SysAdminController();

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Não autenticado');

        $controller->maintenanceStatus();
    }

    public function testMaintenanceStatusThrowsAuthExceptionWhenUserIsNotAdmin(): void
    {
        $this->seedAuthenticatedSession(2001, 'Regular User', false);

        $controller = new SysAdminController();

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Acesso negado');

        $controller->maintenanceStatus();
    }

    public function testGrantAccessReturnsBadRequestWhenUserIdIsMissing(): void
    {
        $this->seedAuthenticatedSession(2002, 'Admin User', true);
        $_POST['days'] = 7;
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $controller = new SysAdminController();

        $response = $controller->grantAccess();

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'Email ou ID do usuario e obrigatorio',
        ], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testMaintenanceStatusReturnsSuccessResponseForAdmin(): void
    {
        $this->seedAuthenticatedSession(2003, 'Admin User', true);

        $controller = new SysAdminController();

        $response = $controller->maintenanceStatus();
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($payload['success']);
        $this->assertArrayHasKey('active', $payload['data']);
        $this->assertArrayHasKey('data', $payload['data']);
    }

    public function testCleanupErrorLogsPassesDaysAndScopeToService(): void
    {
        $this->seedAuthenticatedSession(2004, 'Admin User', true);
        $_GET['days'] = '60';
        $_GET['include_unresolved'] = '1';

        $opsService = $this->createMock(SysAdminOpsService::class);
        $opsService
            ->expects($this->once())
            ->method('cleanupErrorLogs')
            ->with(60, true)
            ->willReturn(12);

        $controller = new SysAdminController(null, $opsService);

        $response = $controller->cleanupErrorLogs();
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($payload['success']);
        $this->assertSame('12 log(s) com mais de 60 dia(s) removido(s)', $payload['message']);
        $this->assertSame(12, $payload['data']['count']);
        $this->assertSame(60, $payload['data']['days']);
        $this->assertTrue($payload['data']['include_unresolved']);
    }

    private function seedAuthenticatedSession(int $userId, string $name, bool $isAdmin): void
    {
        $this->startIsolatedSession('sysadmin-controller-test');

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
