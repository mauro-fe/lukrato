<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api\User;

use Application\Controllers\Api\User\BootstrapController;
use Application\Models\Usuario;
use PHPUnit\Framework\TestCase;
use Tests\Support\SessionIsolation;

class BootstrapControllerTest extends TestCase
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
        $this->resetSessionState();
        parent::tearDown();
    }

    public function testShowReturnsAdminRuntimeConfigForAuthenticatedUser(): void
    {
        $this->seedAuthenticatedSession();
        $_GET = [
            'menu' => 'perfil',
            'view_path' => 'perfil',
            'view_id' => 'perfil',
        ];

        $controller = new BootstrapController();

        $response = $controller->show();
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($payload['success']);
        $this->assertSame(rtrim(BASE_URL, '/') . '/', $payload['data']['baseUrl']);
        $this->assertSame(rtrim(BASE_URL, '/') . '/', $payload['data']['apiBaseUrl']);
        $this->assertSame(77, $payload['data']['userId']);
        $this->assertSame('Maria Silva', $payload['data']['username']);
        $this->assertSame('maria@example.com', $payload['data']['userEmail']);
        $this->assertSame('perfil', $payload['data']['currentMenu']);
        $this->assertSame('perfil', $payload['data']['currentViewId']);
        $this->assertSame('perfil', $payload['data']['currentViewPath']);
        $this->assertSame('light', $payload['data']['userTheme']);
        $this->assertTrue($payload['data']['tourCompleted']);
        $this->assertFalse($payload['data']['needsDisplayNamePrompt']);
        $this->assertSame(rtrim(BASE_URL, '/') . '/uploads/avatar-maria.png', $payload['data']['userAvatar']);
        $this->assertSame([
            'position_x' => 65,
            'position_y' => 35,
            'zoom' => 1.25,
        ], $payload['data']['userAvatarSettings']);
        $this->assertSame([
            'settings' => [
                'auto_offer' => false,
            ],
            'tour_completed' => [
                'perfil.desktop' => 'v2',
            ],
            'offer_dismissed' => [],
            'tips_seen' => [
                'perfil' => 'v1',
            ],
        ], $payload['data']['helpCenter']);
        $this->assertSame('perfil', $payload['data']['pageContext']['currentMenu']);
        $this->assertSame('perfil', $payload['data']['pageContext']['currentViewId']);
        $this->assertSame('perfil', $payload['data']['pageContext']['currentViewPath']);
        $this->assertArrayHasKey('bundle', $payload['data']);
        $this->assertArrayHasKey('pageJsViewId', $payload['data']['bundle']);
    }

    private function seedAuthenticatedSession(): void
    {
        $this->startIsolatedSession('bootstrap-controller-test');

        $user = new Usuario();
        $user->id = 77;
        $user->nome = 'Maria Silva';
        $user->email = 'maria@example.com';
        $user->theme_preference = 'light';
        $user->is_admin = 0;
        $user->avatar = 'uploads/avatar-maria.png';
        $user->avatar_focus_x = 65;
        $user->avatar_focus_y = 35;
        $user->avatar_zoom = 1.25;
        $user->tour_completed_at = '2024-04-02 10:30:00';
        $user->dashboard_preferences = [
            'help_center' => [
                'settings' => [
                    'auto_offer' => false,
                ],
                'tour_completed' => [
                    'perfil.desktop' => 'v2',
                ],
                'tips_seen' => [
                    'perfil' => 'v1',
                ],
            ],
        ];

        $_SESSION['usuario_logged_in'] = true;
        $_SESSION['user_id'] = 77;
        $_SESSION['usuario_nome'] = 'Maria Silva';
        $_SESSION['last_activity'] = time();
        $_SESSION['usuario_cache'] = [
            'id' => 77,
            'data' => $user,
        ];
    }
}
