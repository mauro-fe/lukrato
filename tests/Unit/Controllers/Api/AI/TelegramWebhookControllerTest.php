<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers\Api\AI;

use Application\Controllers\Api\AI\TelegramWebhookController;
use Application\Services\AI\Telegram\TelegramWebhookWorkflowService;
use Illuminate\Database\Capsule\Manager as Capsule;
use PHPUnit\Framework\TestCase;

class TelegramWebhookControllerTest extends TestCase
{
    private ?string $originalSecret = null;
    private string|false $originalSecretGetenv = false;
    private ?string $originalHeader = null;
    /** @var list<int> */
    private array $createdAiLogIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalSecret = $_ENV['TELEGRAM_WEBHOOK_SECRET'] ?? null;
        $this->originalSecretGetenv = getenv('TELEGRAM_WEBHOOK_SECRET');
        $this->originalHeader = $_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? null;

        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    }

    protected function tearDown(): void
    {
        if ($this->originalSecret === null) {
            unset($_ENV['TELEGRAM_WEBHOOK_SECRET']);
        } else {
            $_ENV['TELEGRAM_WEBHOOK_SECRET'] = $this->originalSecret;
        }

        if ($this->originalSecretGetenv === false) {
            putenv('TELEGRAM_WEBHOOK_SECRET');
        } else {
            putenv('TELEGRAM_WEBHOOK_SECRET=' . $this->originalSecretGetenv);
        }

        if ($this->originalHeader === null) {
            unset($_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN']);
        } else {
            $_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] = $this->originalHeader;
        }

        if ($this->createdAiLogIds !== []) {
            Capsule::table('ai_logs')->whereIn('id', $this->createdAiLogIds)->delete();
        }

        unset($_SERVER['REMOTE_ADDR']);

        parent::tearDown();
    }

    public function testReceiveReturnsForbiddenWhenSecretIsInvalid(): void
    {
        $_ENV['TELEGRAM_WEBHOOK_SECRET'] = 'secret';
        putenv('TELEGRAM_WEBHOOK_SECRET=secret');
        $_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] = 'invalid';

        $workflow = $this->createMock(TelegramWebhookWorkflowService::class);
        $workflow->expects($this->never())->method('handleWebhookBody');

        $controller = new class($workflow, '{"ok":true}') extends TelegramWebhookController {
            public function __construct(
                ?TelegramWebhookWorkflowService $workflowService,
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

        $log = Capsule::table('ai_logs')
            ->where('channel', 'telegram')
            ->where('source', 'webhook')
            ->orderByDesc('id')
            ->first();

        $this->assertNotNull($log);
        $this->createdAiLogIds[] = (int) $log->id;
        $this->assertSame(0, (int) $log->success);
        $this->assertSame('Telegram webhook secret invalido', $log->error_message);
    }

    public function testReceiveDelegatesToWorkflowWhenSecretMatches(): void
    {
        $_ENV['TELEGRAM_WEBHOOK_SECRET'] = 'secret';
        putenv('TELEGRAM_WEBHOOK_SECRET=secret');
        $_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] = 'secret';
        $rawBody = '{"update_id":123}';

        $workflow = $this->createMock(TelegramWebhookWorkflowService::class);
        $workflow
            ->expects($this->once())
            ->method('handleWebhookBody')
            ->with($rawBody);

        $controller = new class($workflow, $rawBody) extends TelegramWebhookController {
            public function __construct(
                ?TelegramWebhookWorkflowService $workflowService,
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
        $this->assertSame(['ok' => true], json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }
}
