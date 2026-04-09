<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Admin;

use Application\Services\Admin\AiAdminWorkflowService;
use PHPUnit\Framework\TestCase;

class AiAdminWorkflowServiceTest extends TestCase
{
    private array $originalEnv = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalEnv = [
            'AI_PROVIDER' => getenv('AI_PROVIDER'),
            'OPENAI_API_KEY' => getenv('OPENAI_API_KEY'),
            'OPENAI_MODEL' => getenv('OPENAI_MODEL'),
        ];
    }

    protected function tearDown(): void
    {
        unset($_ENV['AI_PROVIDER'], $_ENV['OPENAI_API_KEY'], $_ENV['OPENAI_MODEL']);

        $this->restoreEnv('AI_PROVIDER');
        $this->restoreEnv('OPENAI_API_KEY');
        $this->restoreEnv('OPENAI_MODEL');

        parent::tearDown();
    }

    public function testHealthProxyReturnsOpenAiConfigStatus(): void
    {
        $this->setEnv('AI_PROVIDER', 'openai');
        $this->unsetEnv('OPENAI_API_KEY');
        $this->setEnv('OPENAI_MODEL', 'gpt-4o-mini');

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
        $this->setEnv('AI_PROVIDER', 'openai');
        $this->unsetEnv('OPENAI_API_KEY');

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

    private function setEnv(string $key, string $value): void
    {
        $_ENV[$key] = $value;
        putenv($key . '=' . $value);
    }

    private function unsetEnv(string $key): void
    {
        unset($_ENV[$key]);
        putenv($key);
    }

    private function restoreEnv(string $key): void
    {
        $value = $this->originalEnv[$key] ?? false;

        if ($value === false) {
            putenv($key);
            return;
        }

        putenv($key . '=' . $value);
    }
}
