<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Auth;

use Application\Controllers\Auth\GoogleCallbackController;
use Application\Services\Auth\GoogleAuthService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tests\Support\SessionIsolation;

class GoogleCallbackControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
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

    public function testConfirmPageReturnsRedirectWhenSessionHasNoPendingUser(): void
    {
        $controller = new GoogleCallbackController(Mockery::mock(GoogleAuthService::class));

        $response = $controller->confirmPage();

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(BASE_URL . 'login', $response->getHeaders()['Location'] ?? null);
    }

    public function testConfirmPageReturnsHtmlResponseWhenPendingUserExists(): void
    {
        $this->startIsolatedSession('google-callback-controller-test');
        $_SESSION['google_pending_user'] = [
            'name' => 'Maria Google',
            'email' => 'maria@lukrato.com',
            'picture' => 'https://example.com/avatar.png',
        ];

        $controller = new GoogleCallbackController(Mockery::mock(GoogleAuthService::class));
        $response = $controller->confirmPage();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('Maria Google', $response->getContent());
        $this->assertStringContainsString('maria@lukrato.com', $response->getContent());
        $this->assertStringContainsString('Criar sua conta', $response->getContent());
    }
}
