<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Settings;

use Application\Controllers\Settings\AccountController;
use Application\Core\Exceptions\AuthException;
use PHPUnit\Framework\TestCase;
use Tests\Support\SessionIsolation;

class AccountControllerTest extends TestCase
{
    use SessionIsolation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetSessionState();
        $_SERVER['REQUEST_METHOD'] = 'DELETE';
    }

    protected function tearDown(): void
    {
        unset($_SERVER['REQUEST_METHOD']);
        $this->resetSessionState();
        parent::tearDown();
    }

    public function testDeleteThrowsAuthExceptionWhenSessionIsMissing(): void
    {
        $controller = new AccountController();

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Nao autenticado');

        $controller->delete();
    }
}
