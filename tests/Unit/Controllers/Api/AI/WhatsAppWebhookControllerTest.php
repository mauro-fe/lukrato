<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api\AI;

use Application\Controllers\Api\AI\WhatsAppWebhookController;
use Application\Services\AI\WhatsApp\WhatsAppWebhookWorkflowService;
use PHPUnit\Framework\TestCase;

class WhatsAppWebhookControllerTest extends TestCase
{
    private ?string $originalVerifyToken = null;
    private ?string $originalSecret = null;
    private ?string $originalSignature = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalVerifyToken = $_ENV['WHATSAPP_VERIFY_TOKEN'] ?? null;
        $this->originalSecret = $_ENV['WHATSAPP_APP_SECRET'] ?? null;
        $this->originalSignature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? null;

        $_GET = [];
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    }

    protected function tearDown(): void
    {
        if ($this->originalVerifyToken === null) {
            unset($_ENV['WHATSAPP_VERIFY_TOKEN']);
        } else {
            $_ENV['WHATSAPP_VERIFY_TOKEN'] = $this->originalVerifyToken;
        }

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

        $_GET = [];
        unset($_SERVER['REMOTE_ADDR']);

        parent::tearDown();
    }

    public function testVerifyReturnsChallengeWhenTokenMatches(): void
    {
        $_ENV['WHATSAPP_VERIFY_TOKEN'] = 'verify-me';
        $_GET['hub.mode'] = 'subscribe';
        $_GET['hub.verify_token'] = 'verify-me';
        $_GET['hub.challenge'] = 'challenge-123';

        $controller = new WhatsAppWebhookController();
        $response = $controller->verify();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('challenge-123', $response->getContent());
    }

    public function testReceiveReturnsForbiddenWhenSignatureIsInvalid(): void
    {
        $_ENV['WHATSAPP_APP_SECRET'] = 'secret';
        $_SERVER['HTTP_X_HUB_SIGNATURE_256'] = 'sha256=invalid';

        $workflow = $this->createMock(WhatsAppWebhookWorkflowService::class);
        $workflow->expects($this->never())->method('handleWebhookBody');

        $controller = new class($workflow, '{"entry":[{"changes":[{"value":{}}]}]}') extends WhatsAppWebhookController {
            public function __construct(
                ?WhatsAppWebhookWorkflowService $workflowService,
                private string $rawBody
            ) {
                parent::__construct($workflowService);
            }

            protected function readRawBody(): string
            {
                return $this->rawBody;
            }
        };

        $response = $controller->receive();

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame('Forbidden', $response->getContent());
    }

    public function testReceiveDelegatesToWorkflowWhenSignatureIsValid(): void
    {
        $rawBody = '{"entry":[{"changes":[{"value":{"statuses":[]}}]}]}';
        $_ENV['WHATSAPP_APP_SECRET'] = 'secret';
        $_SERVER['HTTP_X_HUB_SIGNATURE_256'] = 'sha256=' . hash_hmac('sha256', $rawBody, 'secret');

        $workflow = $this->createMock(WhatsAppWebhookWorkflowService::class);
        $workflow
            ->expects($this->once())
            ->method('handleWebhookBody')
            ->with($rawBody);

        $controller = new class($workflow, $rawBody) extends WhatsAppWebhookController {
            public function __construct(
                ?WhatsAppWebhookWorkflowService $workflowService,
                private string $rawBody
            ) {
                parent::__construct($workflowService);
            }

            protected function readRawBody(): string
            {
                return $this->rawBody;
            }
        };

        $response = $controller->receive();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('OK', $response->getContent());
    }
}
