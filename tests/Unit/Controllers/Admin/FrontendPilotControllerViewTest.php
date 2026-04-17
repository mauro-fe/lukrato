<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Admin;

use Application\Controllers\Admin\FrontendPilotController;
use Application\Core\Exceptions\HttpResponseException;
use Application\Core\Response;
use Application\Models\Usuario;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use Tests\Support\SessionIsolation;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class FrontendPilotControllerViewTest extends TestCase
{
    use SessionIsolation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetSessionState();
    }

    protected function tearDown(): void
    {
        $this->resetSessionState();
        parent::tearDown();
    }

    public function testIndexReturnsHtmlResponseForAuthenticatedUser(): void
    {
        $this->seedAuthenticatedUserSession(1001, 'Frontend Pilot User');

        $controller = new TestableFrontendPilotController();
        $response = $controller->index();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/html; charset=utf-8', $response->getHeaders()['Content-Type'] ?? null);
        $this->assertSame('frontend-pilot-view', $response->getContent());
        $this->assertSame('admin/frontend-pilot/index', $controller->viewPath);
        $this->assertSame('Frontend Pilot', $controller->viewData['pageTitle'] ?? null);
        $this->assertSame('perfil', $controller->viewData['menu'] ?? null);
        $this->assertArrayNotHasKey('pilotBootstrapConfig', $controller->viewData);
    }

    public function testIndexThrowsRedirectResponseWhenSessionIsMissing(): void
    {
        $controller = new TestableFrontendPilotController();

        try {
            $controller->index();
            self::fail('Expected redirect response exception was not thrown.');
        } catch (HttpResponseException $e) {
            $this->assertSame(302, $e->getResponse()->getStatusCode());
            $this->assertSame(BASE_URL . 'login', $e->getResponse()->getHeaders()['Location'] ?? null);
        }
    }

    private function seedAuthenticatedUserSession(int $userId, string $name): void
    {
        $this->startIsolatedSession('frontend-pilot-view-controller-test');

        $user = new TestFrontendPilotViewUser();
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

final class TestFrontendPilotViewUser extends Usuario
{
    public function isPro(): bool
    {
        return false;
    }
}

final class TestableFrontendPilotController extends FrontendPilotController
{
    public ?string $viewPath = null;
    public array $viewData = [];

    protected function renderResponse(string $viewPath, array $data = [], ?string $header = null, ?string $footer = null): Response
    {
        $this->viewPath = $viewPath;
        $this->viewData = $data;

        return Response::htmlResponse('frontend-pilot-view');
    }
}
