<?php

declare(strict_types=1);

namespace Tests\Unit\Services\AI;

use Application\Config\AiRuntimeConfig;
use Application\Config\TelegramRuntimeConfig;
use Application\Config\WhatsAppRuntimeConfig;
use Application\Container\ApplicationContainer;
use Application\Services\AI\Media\AudioTranscriptionService;
use Application\Services\AI\Media\ImageAnalysisService;
use Application\Services\AI\Media\OpenAIAudioHttpClient;
use Application\Services\AI\Media\OpenAIVisionHttpClient;
use Application\Services\AI\Providers\OllamaHttpClient;
use Application\Services\AI\Providers\OllamaProvider;
use Application\Services\AI\Telegram\TelegramBotClient;
use Application\Services\AI\Telegram\TelegramFileHttpClient;
use Application\Services\AI\Telegram\TelegramFileDownloader;
use Application\Services\AI\Telegram\TelegramService;
use Application\Services\AI\WhatsApp\WhatsAppGraphClient;
use Application\Services\AI\WhatsApp\WhatsAppMediaHttpClient;
use Application\Services\AI\WhatsApp\WhatsAppMediaDownloader;
use Application\Services\AI\WhatsApp\WhatsAppService;
use Illuminate\Container\Container as IlluminateContainer;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class AiTransportDependencyResolutionTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();
        ApplicationContainer::flush();
    }

    protected function tearDown(): void
    {
        ApplicationContainer::flush();
        parent::tearDown();
    }

    public function testAiTransportServicesResolveNamedHttpClientsFromContainerWhenAvailable(): void
    {
        $whatsAppClient = Mockery::mock(WhatsAppGraphClient::class);
        $whatsAppMediaClient = Mockery::mock(WhatsAppMediaHttpClient::class);
        $telegramClient = Mockery::mock(TelegramBotClient::class);
        $telegramFileClient = Mockery::mock(TelegramFileHttpClient::class);
        $audioClient = Mockery::mock(OpenAIAudioHttpClient::class);
        $visionClient = Mockery::mock(OpenAIVisionHttpClient::class);
        $ollamaClient = Mockery::mock(OllamaHttpClient::class);
        $aiRuntimeConfig = new AiRuntimeConfig();
        $telegramRuntimeConfig = new TelegramRuntimeConfig();
        $whatsAppRuntimeConfig = new WhatsAppRuntimeConfig();

        $container = new IlluminateContainer();
        $container->instance(AiRuntimeConfig::class, $aiRuntimeConfig);
        $container->instance(TelegramRuntimeConfig::class, $telegramRuntimeConfig);
        $container->instance(WhatsAppRuntimeConfig::class, $whatsAppRuntimeConfig);
        $container->instance(WhatsAppGraphClient::class, $whatsAppClient);
        $container->instance(WhatsAppMediaHttpClient::class, $whatsAppMediaClient);
        $container->instance(TelegramBotClient::class, $telegramClient);
        $container->instance(TelegramFileHttpClient::class, $telegramFileClient);
        $container->instance(OpenAIAudioHttpClient::class, $audioClient);
        $container->instance(OpenAIVisionHttpClient::class, $visionClient);
        $container->instance(OllamaHttpClient::class, $ollamaClient);
        ApplicationContainer::setInstance($container);

        $whatsAppService = new WhatsAppService();
        $whatsAppMediaDownloader = new WhatsAppMediaDownloader();
        $telegramService = new TelegramService();
        $telegramFileDownloader = new TelegramFileDownloader();
        $audioTranscriptionService = new AudioTranscriptionService();
        $imageAnalysisService = new ImageAnalysisService();
        $ollamaProvider = new OllamaProvider();

        $this->assertSame($whatsAppClient, $this->readProperty($whatsAppService, 'http'));
        $this->assertSame($whatsAppMediaClient, $this->readProperty($whatsAppMediaDownloader, 'http'));
        $this->assertSame($telegramClient, $this->readProperty($telegramService, 'http'));
        $this->assertSame($telegramFileClient, $this->readProperty($telegramFileDownloader, 'http'));
        $this->assertSame($audioClient, $this->readProperty($audioTranscriptionService, 'client'));
        $this->assertSame($visionClient, $this->readProperty($imageAnalysisService, 'client'));
        $this->assertSame($ollamaClient, $this->readProperty($ollamaProvider, 'client'));
        $this->assertSame($whatsAppRuntimeConfig, $this->readProperty($whatsAppService, 'runtimeConfig'));
        $this->assertSame($whatsAppRuntimeConfig, $this->readProperty($whatsAppMediaDownloader, 'runtimeConfig'));
        $this->assertSame($telegramRuntimeConfig, $this->readProperty($telegramService, 'runtimeConfig'));
        $this->assertSame($telegramRuntimeConfig, $this->readProperty($telegramFileDownloader, 'runtimeConfig'));
        $this->assertSame($aiRuntimeConfig, $this->readProperty($audioTranscriptionService, 'runtimeConfig'));
        $this->assertSame($aiRuntimeConfig, $this->readProperty($imageAnalysisService, 'runtimeConfig'));
        $this->assertSame($aiRuntimeConfig, $this->readProperty($ollamaProvider, 'runtimeConfig'));
    }

    private function readProperty(object $object, string $property): mixed
    {
        $reflection = new \ReflectionProperty($object, $property);
        $reflection->setAccessible(true);

        return $reflection->getValue($object);
    }
}
