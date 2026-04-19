<?php

declare(strict_types=1);

namespace Tests\Unit\Services\AI;

use Application\DTO\AI\AIRequestDTO;
use Application\Enums\AI\AIChannel;
use Application\Enums\AI\IntentType;
use Application\Services\AI\Contracts\AIProvider;
use Application\Services\AI\Handlers\ChatHandlerV2;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class ChatHandlerV2Test extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testCapabilityQuestionExplainsThatBotCanRegisterTransactions(): void
    {
        $provider = Mockery::mock(AIProvider::class);
        $provider->shouldNotReceive('chat');

        $handler = new ChatHandlerV2();
        $handler->setProvider($provider);

        $request = new AIRequestDTO(
            userId: 1,
            message: 'você nao consegue lancar pra mim?',
            intent: IntentType::CHAT,
            channel: AIChannel::WEB
        );

        $response = $handler->handle($request);

        $this->assertTrue($response->success);
        $this->assertStringContainsString('Consigo sim', $response->message);
        $this->assertStringContainsString('mercado 30 hoje', mb_strtolower($response->message));
        $this->assertEquals('create_lancamento', $response->data['action_hint'] ?? null);
        $this->assertCount(3, $response->data['quick_replies'] ?? []);
    }

    public function testProviderExceptionKeepsFriendlyMessageButPreservesInternalLogError(): void
    {
        $provider = Mockery::mock(AIProvider::class);
        $provider->shouldReceive('chat')
            ->once()
            ->andThrow(new \RuntimeException('cURL error 6: Could not resolve host: api.openai.com'));

        $handler = new ChatHandlerV2();
        $handler->setProvider($provider);

        $request = new AIRequestDTO(
            userId: 1,
            message: 'ontem gastei com mercado',
            intent: IntentType::CHAT,
            channel: AIChannel::WEB,
            context: ['usuario_nome' => 'Mauro']
        );

        $response = $handler->handle($request);

        $this->assertFalse($response->success);
        $this->assertSame(
            'O assistente de IA esta indisponivel no momento. Tente novamente em instantes.',
            $response->message
        );
        $this->assertStringContainsString('RuntimeException', (string) $response->logErrorMessage());
        $this->assertStringContainsString('Could not resolve host', (string) $response->logErrorMessage());
        $this->assertStringNotContainsString('Could not resolve host', json_encode($response->toArray(), JSON_UNESCAPED_UNICODE));
    }
}
