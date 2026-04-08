<?php

declare(strict_types=1);

namespace Tests\Unit\Services\AI;

use Application\Container\ApplicationContainer;
use Application\Repositories\ContaRepository;
use Application\Services\AI\AIService;
use Application\Services\AI\Actions\ActionRegistry;
use Application\Services\AI\ChannelConversationService;
use Application\Services\AI\Context\UserContextBuilder;
use Application\Services\AI\Media\MediaRouterService;
use Application\Services\AI\Security\AIRateLimiter;
use Application\Services\AI\Telegram\TelegramService;
use Application\Services\AI\Telegram\TelegramWebhookWorkflowService;
use Application\Services\AI\UserAiWorkflowService;
use Application\Services\AI\WhatsApp\WhatsAppService;
use Application\Services\AI\WhatsApp\WhatsAppWebhookWorkflowService;
use Application\Services\Infrastructure\CacheService;
use Illuminate\Container\Container as IlluminateContainer;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class AiWorkflowDependencyResolutionTest extends TestCase
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

    public function testUserAndConversationServicesResolveDependenciesFromContainerWhenAvailable(): void
    {
        $aiService = Mockery::mock(AIService::class);
        $contextBuilder = Mockery::mock(UserContextBuilder::class);
        $mediaRouterService = Mockery::mock(MediaRouterService::class);

        $container = new IlluminateContainer();
        $container->instance(AIService::class, $aiService);
        $container->instance(UserContextBuilder::class, $contextBuilder);
        $container->instance(MediaRouterService::class, $mediaRouterService);
        ApplicationContainer::setInstance($container);

        $userWorkflowService = new UserAiWorkflowService();
        $conversationService = new ChannelConversationService();

        $this->assertSame($aiService, $this->readProperty($userWorkflowService, 'aiService'));
        $this->assertSame($contextBuilder, $this->readProperty($userWorkflowService, 'contextBuilder'));
        $this->assertSame($mediaRouterService, $this->readProperty($userWorkflowService, 'mediaRouterService'));
        $this->assertSame($aiService, $this->readProperty($conversationService, 'aiService'));
        $this->assertSame($contextBuilder, $this->readProperty($conversationService, 'contextBuilder'));
    }

    public function testWebhookWorkflowServicesResolveDependenciesFromContainerWhenAvailable(): void
    {
        $whatsAppService = Mockery::mock(WhatsAppService::class);
        $telegramService = Mockery::mock(TelegramService::class);
        $conversationService = Mockery::mock(ChannelConversationService::class);
        $mediaRouterService = Mockery::mock(MediaRouterService::class);
        $contaRepository = Mockery::mock(ContaRepository::class);
        $cacheService = Mockery::mock(CacheService::class);
        $actionRegistry = Mockery::mock(ActionRegistry::class);
        $rateLimiter = new AIRateLimiter($cacheService);

        $container = new IlluminateContainer();
        $container->instance(WhatsAppService::class, $whatsAppService);
        $container->instance(TelegramService::class, $telegramService);
        $container->instance(ChannelConversationService::class, $conversationService);
        $container->instance(MediaRouterService::class, $mediaRouterService);
        $container->instance(ContaRepository::class, $contaRepository);
        $container->instance(CacheService::class, $cacheService);
        $container->instance(ActionRegistry::class, $actionRegistry);
        $container->instance(AIRateLimiter::class, $rateLimiter);
        ApplicationContainer::setInstance($container);

        $whatsAppWorkflowService = new WhatsAppWebhookWorkflowService();
        $telegramWorkflowService = new TelegramWebhookWorkflowService();

        $this->assertSame($whatsAppService, $this->readProperty($whatsAppWorkflowService, 'whatsapp'));
        $this->assertSame($conversationService, $this->readProperty($whatsAppWorkflowService, 'conversationService'));
        $this->assertSame($mediaRouterService, $this->readProperty($whatsAppWorkflowService, 'mediaRouterService'));
        $this->assertSame($contaRepository, $this->readProperty($whatsAppWorkflowService, 'contaRepository'));
        $this->assertSame($cacheService, $this->readProperty($whatsAppWorkflowService, 'cache'));
        $this->assertSame($actionRegistry, $this->readProperty($whatsAppWorkflowService, 'actionRegistry'));
        $this->assertSame($rateLimiter, $this->readProperty($whatsAppWorkflowService, 'rateLimiter'));

        $this->assertSame($telegramService, $this->readProperty($telegramWorkflowService, 'telegram'));
        $this->assertSame($conversationService, $this->readProperty($telegramWorkflowService, 'conversationService'));
        $this->assertSame($mediaRouterService, $this->readProperty($telegramWorkflowService, 'mediaRouterService'));
        $this->assertSame($contaRepository, $this->readProperty($telegramWorkflowService, 'contaRepository'));
        $this->assertSame($cacheService, $this->readProperty($telegramWorkflowService, 'cache'));
        $this->assertSame($actionRegistry, $this->readProperty($telegramWorkflowService, 'actionRegistry'));
        $this->assertSame($rateLimiter, $this->readProperty($telegramWorkflowService, 'rateLimiter'));
    }

    private function readProperty(object $object, string $property): mixed
    {
        $reflection = new \ReflectionProperty($object, $property);
        $reflection->setAccessible(true);

        return $reflection->getValue($object);
    }
}
