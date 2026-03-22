<?php

declare(strict_types=1);

namespace Tests\Unit\Bootstrap;

use Application\Bootstrap\SecurityHeaders;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class SecurityHeadersTest extends TestCase
{
    private ?string $previousAppEnv = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousAppEnv = $_ENV['APP_ENV'] ?? null;
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

    private function invokeCsp(): string
    {
        $headers = new SecurityHeaders();
        $method = new ReflectionMethod(SecurityHeaders::class, 'getCSP');
        $method->setAccessible(true);

        /** @var string $csp */
        $csp = $method->invoke($headers);

        return $csp;
    }
}
