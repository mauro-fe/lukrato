<?php

declare(strict_types=1);

namespace Tests\Unit\Services\AI;

use Application\Services\AI\Security\AIRateLimiter;
use PHPUnit\Framework\TestCase;

class AIRateLimiterTest extends TestCase
{
    private string $storagePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storagePath = sys_get_temp_dir() . '/lukrato-ai-rate-limit-' . bin2hex(random_bytes(6));
        $_ENV['REDIS_ENABLED'] = 'false';
        $_ENV['STORAGE_PATH'] = $this->storagePath;
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->storagePath);
        unset($_ENV['STORAGE_PATH'], $_ENV['REDIS_ENABLED']);

        parent::tearDown();
    }

    public function testAllowsRequestsUntilConfiguredLimit(): void
    {
        $limiter = new AIRateLimiter();

        $this->assertTrue($limiter->allow('user', 'chat', 'user-1', 2, 60));
        $this->assertTrue($limiter->allow('user', 'chat', 'user-1', 2, 60));
        $this->assertFalse($limiter->allow('user', 'chat', 'user-1', 2, 60));
    }

    public function testDifferentBucketsRemainIsolated(): void
    {
        $limiter = new AIRateLimiter();

        $this->assertTrue($limiter->allow('user', 'chat', 'same-id', 1, 60));
        $this->assertFalse($limiter->allow('user', 'chat', 'same-id', 1, 60));
        $this->assertTrue($limiter->allow('user', 'suggest_category', 'same-id', 1, 60));
    }

    public function testDifferentIdentifiersDoNotShareAttempts(): void
    {
        $limiter = new AIRateLimiter();

        $this->assertTrue($limiter->allow('ip', 'chat', '10.0.0.1', 1, 60));
        $this->assertTrue($limiter->allow('ip', 'chat', '10.0.0.2', 1, 60));
    }

    private function deleteDirectory(string $path): void
    {
        if ($path === '' || !is_dir($path)) {
            return;
        }

        $items = scandir($path);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $fullPath = $path . DIRECTORY_SEPARATOR . $item;
            if (is_dir($fullPath)) {
                $this->deleteDirectory($fullPath);
                continue;
            }

            @unlink($fullPath);
        }

        @rmdir($path);
    }
}
