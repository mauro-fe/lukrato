<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api\Financas;

use Application\Controllers\Api\Financas\MetricsController;
use Application\Core\Exceptions\AuthException;
use PHPUnit\Framework\TestCase;

class MetricsControllerTest extends TestCase
{
    public function testMetricsThrowsAuthExceptionWhenSessionIsMissing(): void
    {
        $controller = new MetricsController();

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Nao autenticado');

        $controller->metrics();
    }
}
