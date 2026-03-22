<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api\User;

use Application\Controllers\Api\User\SecurityController;
use PHPUnit\Framework\TestCase;
use Tests\Support\SessionIsolation;

class SecurityControllerTest extends TestCase
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

    public function testRefreshCsrfReturnsTokenPayload(): void
    {
        $_REQUEST['token_id'] = 'dashboard_main';

        $controller = new SecurityController();

        $response = $controller->refreshCsrf();
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($payload['success']);
        $this->assertSame('Success', $payload['message']);
        $this->assertSame('dashboard_main', $payload['data']['token_id']);
        $this->assertIsString($payload['data']['token']);
        $this->assertGreaterThan(0, strlen($payload['data']['token']));
        $this->assertIsInt($payload['data']['ttl']);
    }
}
