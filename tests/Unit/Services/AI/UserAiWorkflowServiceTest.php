<?php

declare(strict_types=1);

namespace Tests\Unit\Services\AI;

use Application\DTO\AI\AIRequestDTO;
use Application\DTO\AI\AIResponseDTO;
use Application\Enums\AI\AIChannel;
use Application\Enums\AI\IntentType;
use Application\Services\AI\AIService;
use Application\Services\AI\Context\UserContextBuilder;
use Application\Services\AI\Media\MediaProcessingResult;
use Application\Services\AI\Media\MediaRouterService;
use Application\Services\AI\UserAiWorkflowService;
use InvalidArgumentException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class UserAiWorkflowServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testChatDispatchesWebRequestAndReturnsDerivedMessageAsNull(): void
    {
        $aiService = Mockery::mock(AIService::class);
        $aiService
            ->shouldReceive('dispatch')
            ->once()
            ->with(Mockery::on(function (AIRequestDTO $request): bool {
                return $request->userId === 77
                    && $request->message === 'Me ajuda com meu saldo'
                    && $request->channel === AIChannel::WEB;
            }))
            ->andReturn(AIResponseDTO::fromRule('Vamos analisar.', [], IntentType::CHAT));

        $contextBuilder = Mockery::mock(UserContextBuilder::class);
        $contextBuilder
            ->shouldReceive('build')
            ->once()
            ->with(77)
            ->andReturn(['saldo' => 1000]);

        $service = new UserAiWorkflowService($aiService, $contextBuilder);

        $result = $service->chat(77, 'Me ajuda com meu saldo');

        $this->assertNull($result['derived_message']);
        $this->assertTrue($result['response']->success);
        $this->assertSame('Vamos analisar.', $result['response']->message);
    }

    public function testChatCombinesAttachmentDerivedTextIntoDispatchedMessage(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'lukrato-ai-');
        file_put_contents($tempFile, 'fake-audio-content');

        $aiService = Mockery::mock(AIService::class);
        $aiService
            ->shouldReceive('dispatch')
            ->once()
            ->with(Mockery::on(function (AIRequestDTO $request): bool {
                return $request->userId === 88
                    && $request->message === "Registrar audio\ngastei 25.00 no cafe"
                    && $request->channel === AIChannel::WEB;
            }))
            ->andReturn(AIResponseDTO::fromRule('Entendi.', [], IntentType::CHAT));

        $contextBuilder = Mockery::mock(UserContextBuilder::class);
        $contextBuilder
            ->shouldReceive('build')
            ->once()
            ->with(88)
            ->andReturn([]);

        $mediaRouter = Mockery::mock(MediaRouterService::class);
        $mediaRouter
            ->shouldReceive('process')
            ->once()
            ->andReturn(new MediaProcessingResult(
                success: true,
                mediaType: 'audio',
                operation: 'audio_transcription',
                text: 'gastei 25.00 no cafe',
            ));

        $service = new UserAiWorkflowService($aiService, $contextBuilder, $mediaRouter);

        $result = $service->chat(88, 'Registrar audio', [
            'tmp_name' => $tempFile,
            'type' => 'audio/ogg',
            'name' => 'audio.ogg',
            'size' => 128,
        ]);

        @unlink($tempFile);

        $this->assertSame('gastei 25.00 no cafe', $result['derived_message']);
        $this->assertSame('Entendi.', $result['response']->message);
    }

    public function testSuggestCategoryRejectsShortDescription(): void
    {
        $service = new UserAiWorkflowService(
            Mockery::mock(AIService::class),
            Mockery::mock(UserContextBuilder::class)
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Descrição muito curta para sugerir categoria.');

        $service->suggestCategory(12, 'a');
    }

    public function testExtractTransactionUsesWebChannel(): void
    {
        $aiService = Mockery::mock(AIService::class);
        $aiService
            ->shouldReceive('dispatch')
            ->once()
            ->with(Mockery::on(function (AIRequestDTO $request): bool {
                return $request->userId === 99
                    && $request->message === 'gastei 50 no mercado'
                    && $request->intent === IntentType::EXTRACT_TRANSACTION
                    && $request->channel === AIChannel::WEB;
            }))
            ->andReturn(AIResponseDTO::fromRule('Extraido.', ['valor' => 50], IntentType::EXTRACT_TRANSACTION));

        $service = new UserAiWorkflowService(
            $aiService,
            Mockery::mock(UserContextBuilder::class)
        );

        $response = $service->extractTransaction(99, 'gastei 50 no mercado');

        $this->assertTrue($response->success);
        $this->assertSame('Extraido.', $response->message);
        $this->assertSame(['valor' => 50], $response->data);
    }
}
