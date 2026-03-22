<?php

declare(strict_types=1);

namespace Tests\Unit\Services\AI;

use Application\Services\AI\WhatsApp\WhatsAppWebhookWorkflowService;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class WhatsAppWebhookWorkflowServiceTest extends TestCase
{
    public function testHandleWebhookBodyIgnoresInvalidJson(): void
    {
        $service = new WhatsAppWebhookWorkflowService();
        $service->handleWebhookBody('{invalid-json');

        $this->addToAssertionCount(1);
    }

    public function testNormalizeQuickRepliesTrimsAndLimitsEntries(): void
    {
        $service = new WhatsAppWebhookWorkflowService();
        $method = (new ReflectionClass($service))->getMethod('normalizeQuickReplies');
        $method->setAccessible(true);

        $result = $method->invoke($service, [
            ['label' => '  Primeira opcao bem longa demais  ', 'message' => ' Mensagem 1 '],
            ['label' => 'Segunda', 'message' => 'Mensagem 2'],
            ['label' => 'Terceira', 'message' => 'Mensagem 3'],
            ['label' => 'Quarta', 'message' => 'Mensagem 4'],
            ['label' => '', 'message' => 'ignorar'],
        ]);

        $this->assertSame([
            ['label' => 'Primeira opcao bem l', 'message' => 'Mensagem 1'],
            ['label' => 'Segunda', 'message' => 'Mensagem 2'],
            ['label' => 'Terceira', 'message' => 'Mensagem 3'],
        ], $result);
    }

    public function testBuildSelectionMessageAppendsOptionsWhenMessageHasNoList(): void
    {
        $service = new WhatsAppWebhookWorkflowService();
        $method = (new ReflectionClass($service))->getMethod('buildSelectionMessage');
        $method->setAccessible(true);

        $result = $method->invoke($service, 'Escolha uma conta', [
            ['label' => 'Conta Corrente'],
            ['label' => 'Cartao Nubank'],
        ]);

        $this->assertSame(
            "Escolha uma conta\n\n1. Conta Corrente\n2. Cartao Nubank\n\nResponda com o numero ou toque em uma opcao.",
            $result
        );
    }
}
