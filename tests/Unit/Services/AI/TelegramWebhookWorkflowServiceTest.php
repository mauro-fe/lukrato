<?php

declare(strict_types=1);

namespace Tests\Unit\Services\AI;

use Application\Models\TelegramMessage;
use Application\Services\AI\Telegram\TelegramWebhookWorkflowService;
use Illuminate\Database\Capsule\Manager as Capsule;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class TelegramWebhookWorkflowServiceTest extends TestCase
{
    /** @var list<int> */
    private array $createdAiLogIds = [];

    protected function tearDown(): void
    {
        if ($this->createdAiLogIds !== []) {
            Capsule::table('ai_logs')->whereIn('id', $this->createdAiLogIds)->delete();
        }

        parent::tearDown();
    }

    public function testHandleWebhookBodyIgnoresInvalidJson(): void
    {
        $service = new TelegramWebhookWorkflowService();
        $service->handleWebhookBody('{invalid-json');

        $this->addToAssertionCount(1);
    }

    public function testNormalizeQuickRepliesTrimsAndLimitsEntries(): void
    {
        $service = new TelegramWebhookWorkflowService();
        $method = (new ReflectionClass($service))->getMethod('normalizeQuickReplies');
        $method->setAccessible(true);

        $result = $method->invoke($service, [
            ['label' => '  Primeira opcao bem longa demais  ', 'message' => ' Mensagem 1 '],
            ['label' => 'Segunda', 'message' => 'Mensagem 2'],
            ['label' => 'Terceira', 'message' => 'Mensagem 3'],
            ['label' => 'Quarta', 'message' => 'Mensagem 4'],
        ]);

        $this->assertSame([
            ['label' => 'Primeira opcao bem longa demais', 'message' => 'Mensagem 1'],
            ['label' => 'Segunda', 'message' => 'Mensagem 2'],
            ['label' => 'Terceira', 'message' => 'Mensagem 3'],
        ], $result);
    }

    public function testTelegramSendFailureCreatesAiLogError(): void
    {
        $service = new TelegramWebhookWorkflowService();
        $method = (new ReflectionClass($service))->getMethod('markTelegramSendFailure');
        $method->setAccessible(true);

        $message = new TelegramMessage([
            'user_id' => 123,
            'body' => 'Teste',
        ]);

        $method->invoke($service, $message, '123456', 'send_ai_text', 'chat');

        $log = Capsule::table('ai_logs')
            ->where('channel', 'telegram')
            ->where('source', 'delivery')
            ->orderByDesc('id')
            ->first();

        $this->assertNotNull($log);
        $this->createdAiLogIds[] = (int) $log->id;
        $this->assertSame(123, (int) $log->user_id);
        $this->assertSame('Teste', $log->prompt);
        $this->assertSame(0, (int) $log->success);
        $this->assertStringContainsString('Falha ao enviar resposta para o Telegram', $log->error_message);
    }
}
