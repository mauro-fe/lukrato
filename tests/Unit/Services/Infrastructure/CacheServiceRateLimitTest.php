<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Infrastructure;

use Application\Core\Exceptions\ValidationException;
use Application\Services\Infrastructure\CacheService;
use PHPUnit\Framework\TestCase;

class CacheServiceRateLimitTest extends TestCase
{
    private ?string $previousRedisEnabled = null;
    private ?string $previousStoragePath = null;
    private string $storagePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->previousRedisEnabled = $_ENV['REDIS_ENABLED'] ?? null;
        $this->previousStoragePath = $_ENV['STORAGE_PATH'] ?? null;
        $this->storagePath = sys_get_temp_dir() . '/lukrato-test-storage-rate-limit';

        if (!is_dir($this->storagePath . '/cache')) {
            mkdir($this->storagePath . '/cache', 0755, true);
        }

        $_ENV['REDIS_ENABLED'] = 'false';
        $_ENV['STORAGE_PATH'] = $this->storagePath;
    }

    protected function tearDown(): void
    {
        if ($this->previousRedisEnabled === null) {
            unset($_ENV['REDIS_ENABLED']);
        } else {
            $_ENV['REDIS_ENABLED'] = $this->previousRedisEnabled;
        }

        if ($this->previousStoragePath === null) {
            unset($_ENV['STORAGE_PATH']);
        } else {
            $_ENV['STORAGE_PATH'] = $this->previousStoragePath;
        }

        parent::tearDown();
    }

    public function testCheckRateLimitUsesFallbackWhenRedisIsDisabled(): void
    {
        $cache = new CacheService();
        $key = 'test-rate-limit-' . bin2hex(random_bytes(6));

        $cache->checkRateLimit($key, 1, 60);

        try {
            $cache->checkRateLimit($key, 1, 60);
            $this->fail('Era esperada ValidationException de rate limit.');
        } catch (ValidationException $e) {
            $this->assertSame(429, $e->getCode());
            $this->assertArrayHasKey('rate_limit', $e->getErrors());
        }
    }

    public function testSetAndGetFallbackToFileStorageWhenRedisIsDisabled(): void
    {
        $cache = new CacheService();
        $key = 'test-file-cache-' . bin2hex(random_bytes(6));

        $this->assertTrue($cache->set($key, ['ok' => true], 60));
        $this->assertSame(['ok' => true], $cache->get($key));
    }
}
