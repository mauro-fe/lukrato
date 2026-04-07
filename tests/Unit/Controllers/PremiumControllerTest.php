<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use Application\Controllers\PremiumController;
use Application\Core\Exceptions\AuthException;
use PHPUnit\Framework\TestCase;
use Tests\Support\SessionIsolation;

class PremiumControllerTest extends TestCase
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

    public function testCheckoutThrowsAuthExceptionWhenSessionIsMissing(): void
    {
        $controller = new PremiumController();

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Não autenticado');

        $controller->checkout();
    }

    public function testGetPendingPaymentThrowsAuthExceptionWhenSessionIsMissing(): void
    {
        $controller = new PremiumController();

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Não autenticado');

        $controller->getPendingPayment();
    }
}
