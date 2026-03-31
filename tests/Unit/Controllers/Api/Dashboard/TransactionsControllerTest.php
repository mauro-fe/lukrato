<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api\Dashboard;

use Application\Controllers\Api\Dashboard\TransactionsController;
use Application\Core\Exceptions\AuthException;
use PHPUnit\Framework\TestCase;

class TransactionsControllerTest extends TestCase
{
    public function testTransactionsThrowsAuthExceptionWhenSessionIsMissing(): void
    {
        $controller = new TransactionsController();

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Nao autenticado');

        $controller->transactions();
    }
}
