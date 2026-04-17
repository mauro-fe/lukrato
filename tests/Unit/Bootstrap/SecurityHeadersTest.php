<?php

declare(strict_types=1);

namespace Tests\Unit\Bootstrap;

use Application\Bootstrap\SecurityHeaders;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class SecurityHeadersTest extends TestCase
{
    private ?string $previousAppEnv = null;
    private ?string $previousAllowedOrigins = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousAppEnv = $_ENV['APP_ENV'] ?? null;
        $this->previousAllowedOrigins = $_ENV['ALLOWED_ORIGINS'] ?? null;
        $_ENV['APP_ENV'] = 'production';
        $_SERVER['SCRIPT_NAME'] = '/index.php';
    }

    protected function tearDown(): void
    {
        if ($this->previousAppEnv === null) {
            unset($_ENV['APP_ENV']);
        } else {
            $_ENV['APP_ENV'] = $this->previousAppEnv;
        }

        if ($this->previousAllowedOrigins === null) {
            unset($_ENV['ALLOWED_ORIGINS']);
        } else {
            $_ENV['ALLOWED_ORIGINS'] = $this->previousAllowedOrigins;
        }

        unset($_SERVER['REQUEST_URI'], $_SERVER['SCRIPT_NAME']);

        parent::tearDown();
    }

    public function testPublicRoutesDoNotAllowUnsafeEval(): void
    {
        $_SERVER['REQUEST_URI'] = '/';

        $csp = $this->invokeCsp();

        $this->assertStringNotContainsString("'unsafe-eval'", $csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        $this->assertStringContainsString("manifest-src 'self'", $csp);
    }

    public function testAdminShellRoutesStillAllowUnsafeEvalDuringMigration(): void
    {
        $_SERVER['REQUEST_URI'] = '/dashboard';

        $csp = $this->invokeCsp();

        $this->assertStringContainsString("'unsafe-eval'", $csp);
    }

    public function testApiRoutesDoNotAllowUnsafeEval(): void
    {
        $_SERVER['REQUEST_URI'] = '/api/notifications/read-all';

        $csp = $this->invokeCsp();

        $this->assertStringNotContainsString("'unsafe-eval'", $csp);
    }

    public function testConfiguredAllowedOriginIsAccepted(): void
    {
        $_ENV['ALLOWED_ORIGINS'] = 'https://app.example.com,https://admin.example.com';

        $this->assertTrue($this->invokeIsAllowedOrigin('https://app.example.com/'));
        $this->assertFalse($this->invokeIsAllowedOrigin('https://other.example.com'));
    }

    private function invokeCsp(): string
    {
        $headers = new SecurityHeaders();
        $method = new ReflectionMethod(SecurityHeaders::class, 'getCSP');
        $method->setAccessible(true);

        /** @var string $csp */
        $csp = $method->invoke($headers);

        return $csp;
    }

    private function invokeIsAllowedOrigin(string $origin): bool
    {
        $headers = new SecurityHeaders();
        $method = new ReflectionMethod(SecurityHeaders::class, 'isAllowedOrigin');
        $method->setAccessible(true);

        /** @var bool $allowed */
        $allowed = $method->invoke($headers, $origin);

        return $allowed;
    }
}
