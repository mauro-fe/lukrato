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
            message: 'Voce nao consegue lancar pra mim?',
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
}
