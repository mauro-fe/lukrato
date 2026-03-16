<?php

declare(strict_types=1);

namespace Tests\Unit\Services\AI;

use Application\Services\AI\Telegram\TelegramResponseFormatter;
use PHPUnit\Framework\TestCase;

class TelegramResponseFormatterTest extends TestCase
{
    public function testEscapesHtmlBeforeApplyingMarkdown(): void
    {
        $chunks = TelegramResponseFormatter::format('2 < 3 & **forte**');

        $this->assertCount(1, $chunks);
        $this->assertSame('2 &lt; 3 &amp; <b>forte</b>', $chunks[0]);
    }

    public function testFallsBackToPlainTextWhenMessageNeedsSplit(): void
    {
        $message = str_repeat('**resumo** com comparacao 2 < 3. ', 220);
        $chunks = TelegramResponseFormatter::format($message);

        $this->assertGreaterThan(1, count($chunks));

        foreach ($chunks as $chunk) {
            $this->assertLessThanOrEqual(4096, mb_strlen($chunk));
            $this->assertStringNotContainsString('<b>', $chunk);
            $this->assertStringContainsString('&lt;', $chunk);
            $this->assertStringContainsString('resumo', $chunk);
        }
    }
}
