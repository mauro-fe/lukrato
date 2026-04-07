<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Admin;

use Application\Services\Admin\AiAdminWorkflowService;
use PHPUnit\Framework\TestCase;

class AiAdminWorkflowServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($_ENV['AI_PROVIDER'], $_ENV['OPENAI_API_KEY'], $_ENV['OPENAI_MODEL']);
        parent::tearDown();
    }

    public function testHealthProxyReturnsOpenAiConfigStatus(): void
    {
        $_ENV['AI_PROVIDER'] = 'openai';
        unset($_ENV['OPENAI_API_KEY']);
        $_ENV['OPENAI_MODEL'] = 'gpt-4o-mini';

        $service = new AiAdminWorkflowService();
        $result = $service->healthProxy();

        $this->assertTrue($result['success']);
        $this->assertSame([
            'status' => 'error',
            'service' => 'lukrato-ai',
            'provider' => 'openai',
            'model' => 'gpt-4o-mini',
            'message' => 'OPENAI_API_KEY nao configurada',
        ], $result['data']);
    }

    public function testQuotaReturnsBadRequestWhenApiKeyIsMissing(): void
    {
        $_ENV['AI_PROVIDER'] = 'openai';
        unset($_ENV['OPENAI_API_KEY']);

        $service = new AiAdminWorkflowService();
        $result = $service->quota();

        $this->assertFalse($result['success']);
        $this->assertSame(400, $result['status']);
        $this->assertSame('OPENAI_API_KEY nao configurada', $result['message']);
    }

    public function testChatReturnsValidationErrorWhenMessageIsEmpty(): void
    {
        $service = new AiAdminWorkflowService();
        $result = $service->chat([]);

        $this->assertFalse($result['success']);
        $this->assertSame(422, $result['status']);
        $this->assertSame('Mensagem não pode ser vazia', $result['message']);
    }

    public function testAnalyzeSpendingReturnsValidationErrorWhenNoEntriesAreProvided(): void
    {
        $service = new AiAdminWorkflowService();
        $result = $service->analyzeSpending(99, []);

        $this->assertFalse($result['success']);
        $this->assertSame(422, $result['status']);
        $this->assertSame('Nenhum dado fornecido para analise', $result['message']);
    }
}
