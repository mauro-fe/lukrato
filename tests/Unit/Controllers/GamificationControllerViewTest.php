<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use Application\Controllers\GamificationController;
use Application\Core\Exceptions\HttpResponseException;
use Application\Core\Response;
use Application\Models\Usuario;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use Tests\Support\SessionIsolation;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class GamificationControllerViewTest extends TestCase
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
        $this->seedAuthenticatedUserSession(901, 'Gamification View');

        $controller = new TestableGamificationController();
        $response = $controller->index();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/html; charset=utf-8', $response->getHeaders()['Content-Type'] ?? null);
        $this->assertSame('gamification-view', $response->getContent());
        $this->assertSame('admin/gamification/index', $controller->viewPath);
        $this->assertSame('Gamificação - Lukrato', $controller->viewData['pageTitle'] ?? null);
    }

    public function testIndexThrowsRedirectResponseWhenSessionIsMissing(): void
    {
        $controller = new TestableGamificationController();

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
        $this->startIsolatedSession('gamification-view-controller-test');

        $user = new TestGamificationViewUser();
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

final class TestGamificationViewUser extends Usuario
{
    public function isPro(): bool
    {
        return false;
    }
}

final class TestableGamificationController extends GamificationController
{
    public ?string $viewPath = null;
    public array $viewData = [];

    protected function renderResponse(string $viewPath, array $data = [], ?string $header = null, ?string $footer = null): Response
    {
        $this->viewPath = $viewPath;
        $this->viewData = $data;

        return Response::htmlResponse('gamification-view');
    }
}
