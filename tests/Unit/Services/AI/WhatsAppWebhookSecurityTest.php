<?php

declare(strict_types=1);

namespace Tests\Unit\Services\AI;

use Application\Controllers\Api\AI\WhatsAppWebhookController;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class WhatsAppWebhookSecurityTest extends TestCase
{
    private ?string $originalSecret = null;
    private ?string $originalSignature = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalSecret = $_ENV['WHATSAPP_APP_SECRET'] ?? null;
        $this->originalSignature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? null;
    }

    protected function tearDown(): void
    {
        if ($this->originalSecret === null) {
            unset($_ENV['WHATSAPP_APP_SECRET']);
        } else {
            $_ENV['WHATSAPP_APP_SECRET'] = $this->originalSecret;
        }

        if ($this->originalSignature === null) {
            unset($_SERVER['HTTP_X_HUB_SIGNATURE_256']);
        } else {
            $_SERVER['HTTP_X_HUB_SIGNATURE_256'] = $this->originalSignature;
        }

        parent::tearDown();
    }

    public function testSignatureValidationIsBypassedWhenSecretIsNotConfigured(): void
    {
        unset($_ENV['WHATSAPP_APP_SECRET'], $_SERVER['HTTP_X_HUB_SIGNATURE_256']);

        $this->assertTrue($this->invokeSignatureValidator('{"test":true}'));
    }

    public function testValidSignatureIsAccepted(): void
    {
        $rawBody = '{"entry":[{"id":"abc"}]}';
        $_ENV['WHATSAPP_APP_SECRET'] = 'app-secret-test';
        $_SERVER['HTTP_X_HUB_SIGNATURE_256'] = 'sha256=' . hash_hmac('sha256', $rawBody, 'app-secret-test');

        $this->assertTrue($this->invokeSignatureValidator($rawBody));
    }

    public function testInvalidSignatureIsRejected(): void
    {
        $_ENV['WHATSAPP_APP_SECRET'] = 'app-secret-test';
        $_SERVER['HTTP_X_HUB_SIGNATURE_256'] = 'sha256=invalid';

        $this->assertFalse($this->invokeSignatureValidator('{"entry":[{"id":"abc"}]}'));
    }

    private function invokeSignatureValidator(string $rawBody): bool
    {
        $reflection = new ReflectionClass(WhatsAppWebhookController::class);
        $controller = $reflection->newInstanceWithoutConstructor();
        $method = $reflection->getMethod('isValidWebhookSignature');
        $method->setAccessible(true);

        return (bool) $method->invoke($controller, $rawBody);
    }
}
