<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Auth;

use Application\Controllers\Auth\GoogleLoginController;
use Application\Services\Auth\GoogleAuthService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tests\Support\SessionIsolation;

class GoogleLoginControllerTest extends TestCase
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

    public function testLoginReturnsRedirectResponseToGoogleAuthUrl(): void
    {
        $service = Mockery::mock(GoogleAuthService::class);
        $service->shouldReceive('getAuthUrl')->once()->andReturn('https://accounts.google.com/o/oauth2/auth');

        $controller = new GoogleLoginController($service);
        $response = $controller->login();

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('https://accounts.google.com/o/oauth2/auth', $response->getHeaders()['Location'] ?? null);
    }
}
