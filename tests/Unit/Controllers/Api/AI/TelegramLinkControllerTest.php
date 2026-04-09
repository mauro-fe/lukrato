<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api\AI;

use Application\Controllers\Api\AI\TelegramLinkController;
use Application\Core\Exceptions\AuthException;
use PHPUnit\Framework\TestCase;

class TelegramLinkControllerTest extends TestCase
{
    public function testRequestLinkThrowsAuthExceptionWhenSessionIsMissing(): void
    {
        $controller = new TelegramLinkController();

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Não autenticado');

        $controller->requestLink();
    }
}
