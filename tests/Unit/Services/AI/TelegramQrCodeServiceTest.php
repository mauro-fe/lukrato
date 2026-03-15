<?php

declare(strict_types=1);

namespace Tests\Unit\Services\AI;

use Application\Services\AI\Telegram\TelegramQrCodeService;
use PHPUnit\Framework\TestCase;

class TelegramQrCodeServiceTest extends TestCase
{
    public function testGeneratesSvgDataUriForTelegramLink(): void
    {
        $uri = TelegramQrCodeService::makeDataUri('https://t.me/LukratoBot?start=123456');

        $this->assertStringStartsWith('data:image/svg+xml;base64,', $uri);

        $encoded = substr($uri, strlen('data:image/svg+xml;base64,'));
        $svg = base64_decode($encoded, true);

        $this->assertNotFalse($svg);
        $this->assertStringContainsString('<svg', $svg);
        $this->assertStringContainsString('https://t.me/LukratoBot?start=123456', $svg);
    }
}
