<?php

declare(strict_types=1);

namespace Tests\Unit\Services\AI;

use Application\Services\AI\Telegram\TelegramWebhookWorkflowService;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class TelegramWebhookWorkflowServiceTest extends TestCase
{
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
}
