<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api\Financas;

use Application\Controllers\Api\Financas\ResumoController;
use Application\Core\Exceptions\AuthException;
use PHPUnit\Framework\TestCase;

class ResumoControllerTest extends TestCase
{
    public function testResumoThrowsAuthExceptionWhenSessionIsMissing(): void
    {
        $controller = new ResumoController();

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Nao autenticado');

        $controller->resumo();
    }
}
