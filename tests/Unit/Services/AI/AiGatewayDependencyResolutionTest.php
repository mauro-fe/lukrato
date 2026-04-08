<?php

declare(strict_types=1);

namespace Tests\Unit\Services\AI;

use Application\Container\ApplicationContainer;
use Application\Enums\AI\IntentType;
use Application\Services\AI\AIService;
use Application\Services\AI\Analysis\FinancialAnalysisPreprocessor;
use Application\Services\AI\Contracts\AIProvider;
use Application\Services\AI\Handlers\CategorizationHandler;
use Application\Services\AI\Handlers\ChatHandlerV2;
use Application\Services\AI\Handlers\ConfirmationHandler;
use Application\Services\AI\Handlers\EntityCreationHandler;
use Application\Services\AI\Handlers\FinancialAnalysisHandler;
use Application\Services\AI\Handlers\PayFaturaHandler;
use Application\Services\AI\Handlers\QuickQueryHandler;
use Application\Services\AI\Handlers\TransactionExtractorHandler;
use Application\Services\AI\Helpers\UserCategoryLoader;
use Application\Services\AI\IntentRouter;
use Application\Services\AI\Providers\OpenAIProvider;
use Application\Services\AI\Telegram\TelegramUserResolver;
use Application\Services\AI\WhatsApp\WhatsAppUserResolver;
use Application\Services\Infrastructure\CacheService;
use GuzzleHttp\Client;
use Illuminate\Container\Container as IlluminateContainer;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class AiGatewayDependencyResolutionTest extends TestCase
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

    public function testAiServiceResolvesProviderCacheRouterAndHandlersFromContainerWhenAvailable(): void
    {
        $provider = Mockery::mock(AIProvider::class);
        $cache = Mockery::mock(CacheService::class);
        $intentRouter = Mockery::mock(IntentRouter::class);

        $handlers = [
            IntentType::CHAT->value => $this->mockHandler(ChatHandlerV2::class, $provider),
            IntentType::QUICK_QUERY->value => $this->mockHandler(QuickQueryHandler::class, $provider),
            IntentType::CATEGORIZE->value => $this->mockHandler(CategorizationHandler::class, $provider),
            IntentType::EXTRACT_TRANSACTION->value => $this->mockHandler(TransactionExtractorHandler::class, $provider),
            IntentType::ANALYZE->value => $this->mockHandler(FinancialAnalysisHandler::class, $provider),
            IntentType::CREATE_ENTITY->value => $this->mockHandler(EntityCreationHandler::class, $provider),
            IntentType::CONFIRM_ACTION->value => $this->mockHandler(ConfirmationHandler::class, $provider),
            IntentType::PAY_FATURA->value => $this->mockHandler(PayFaturaHandler::class, $provider),
        ];

        $container = new IlluminateContainer();
        $container->instance(AIProvider::class, $provider);
        $container->instance(CacheService::class, $cache);
        $container->instance(IntentRouter::class, $intentRouter);
        $container->instance(ChatHandlerV2::class, $handlers[IntentType::CHAT->value]);
        $container->instance(QuickQueryHandler::class, $handlers[IntentType::QUICK_QUERY->value]);
        $container->instance(CategorizationHandler::class, $handlers[IntentType::CATEGORIZE->value]);
        $container->instance(TransactionExtractorHandler::class, $handlers[IntentType::EXTRACT_TRANSACTION->value]);
        $container->instance(FinancialAnalysisHandler::class, $handlers[IntentType::ANALYZE->value]);
        $container->instance(EntityCreationHandler::class, $handlers[IntentType::CREATE_ENTITY->value]);
        $container->instance(ConfirmationHandler::class, $handlers[IntentType::CONFIRM_ACTION->value]);
        $container->instance(PayFaturaHandler::class, $handlers[IntentType::PAY_FATURA->value]);
        ApplicationContainer::setInstance($container);

        $service = new AIService();
        $registeredHandlers = $this->readProperty($service, 'handlers');

        $this->assertSame($provider, $this->readProperty($service, 'provider'));
        $this->assertSame($cache, $this->readProperty($service, 'cache'));
        $this->assertSame($intentRouter, $this->readProperty($service, 'intentRouter'));
        $this->assertSame($handlers[IntentType::CHAT->value], $registeredHandlers[IntentType::CHAT->value]);
        $this->assertSame($handlers[IntentType::QUICK_QUERY->value], $registeredHandlers[IntentType::QUICK_QUERY->value]);
        $this->assertSame($handlers[IntentType::CATEGORIZE->value], $registeredHandlers[IntentType::CATEGORIZE->value]);
        $this->assertSame($handlers[IntentType::EXTRACT_TRANSACTION->value], $registeredHandlers[IntentType::EXTRACT_TRANSACTION->value]);
        $this->assertSame($handlers[IntentType::ANALYZE->value], $registeredHandlers[IntentType::ANALYZE->value]);
        $this->assertSame($handlers[IntentType::CREATE_ENTITY->value], $registeredHandlers[IntentType::CREATE_ENTITY->value]);
        $this->assertSame($handlers[IntentType::CONFIRM_ACTION->value], $registeredHandlers[IntentType::CONFIRM_ACTION->value]);
        $this->assertSame($handlers[IntentType::PAY_FATURA->value], $registeredHandlers[IntentType::PAY_FATURA->value]);
    }

    public function testCategorizationAndFinancialAnalysisHandlersResolveDependenciesFromContainerWhenAvailable(): void
    {
        $cache = Mockery::mock(CacheService::class);
        $preprocessor = Mockery::mock(FinancialAnalysisPreprocessor::class);

        $container = new IlluminateContainer();
        $container->instance(CacheService::class, $cache);
        $container->instance(FinancialAnalysisPreprocessor::class, $preprocessor);
        ApplicationContainer::setInstance($container);

        $categorizationHandler = new CategorizationHandler();
        $financialAnalysisHandler = new FinancialAnalysisHandler();

        $this->assertSame($cache, $this->readProperty($categorizationHandler, 'cache'));
        $this->assertSame($cache, $this->readProperty($financialAnalysisHandler, 'cache'));
        $this->assertSame($preprocessor, $this->readProperty($financialAnalysisHandler, 'preprocessor'));
    }

    public function testOpenAiProviderResolvesClientAndCacheFromContainerWhenAvailable(): void
    {
        $cache = Mockery::mock(CacheService::class);
        $client = Mockery::mock(Client::class);

        $container = new IlluminateContainer();
        $container->instance(CacheService::class, $cache);
        $container->instance(Client::class, $client);
        ApplicationContainer::setInstance($container);

        $provider = new OpenAIProvider();

        $this->assertSame($cache, $this->readProperty($provider, 'cache'));
        $this->assertSame($client, $this->readProperty($provider, 'client'));
    }

    public function testUserCategoryLoaderUsesContainerCacheWhenAvailable(): void
    {
        $cache = Mockery::mock(CacheService::class);
        $cache->shouldReceive('get')
            ->once()
            ->with('ai:user_categories:99')
            ->andReturn(['Alimentação', 'Alimentação > Delivery']);

        $container = new IlluminateContainer();
        $container->instance(CacheService::class, $cache);
        ApplicationContainer::setInstance($container);

        $this->assertSame(
            ['Alimentação', 'Alimentação > Delivery'],
            UserCategoryLoader::load(99)
        );
    }

    public function testChannelResolversUseContainerCacheWhenAvailable(): void
    {
        $whatsAppCache = Mockery::mock(CacheService::class);
        $generatedWhatsAppCode = null;
        $whatsAppCache->shouldReceive('set')
            ->once()
            ->withArgs(function (string $key, string $value, int $ttl) use (&$generatedWhatsAppCode): bool {
                $generatedWhatsAppCode = $value;

                return $key === 'whatsapp_verify:12'
                    && preg_match('/^\d{6}$/', $value) === 1
                    && $ttl === 600;
            });

        $container = new IlluminateContainer();
        $container->instance(CacheService::class, $whatsAppCache);
        ApplicationContainer::setInstance($container);

        $whatsAppCode = WhatsAppUserResolver::generateVerificationCode(12);

        $this->assertSame($generatedWhatsAppCode, $whatsAppCode);

        $telegramCache = Mockery::mock(CacheService::class);
        $generatedTelegramCode = null;
        $telegramCache->shouldReceive('set')
            ->once()
            ->ordered()
            ->withArgs(function (string $key, string $value, int $ttl) use (&$generatedTelegramCode): bool {
                $generatedTelegramCode = $value;

                return $key === 'telegram_verify:34'
                    && preg_match('/^\d{6}$/', $value) === 1
                    && $ttl === 600;
            });
        $telegramCache->shouldReceive('set')
            ->once()
            ->ordered()
            ->withArgs(function (string $key, int $value, int $ttl) use (&$generatedTelegramCode): bool {
                return $generatedTelegramCode !== null
                    && $key === 'telegram_code_reverse:' . $generatedTelegramCode
                    && $value === 34
                    && $ttl === 600;
            });

        $container = new IlluminateContainer();
        $container->instance(CacheService::class, $telegramCache);
        ApplicationContainer::setInstance($container);

        $telegramCode = TelegramUserResolver::generateVerificationCodeWithReverse(34);

        $this->assertSame($generatedTelegramCode, $telegramCode);
    }

    private function mockHandler(string $handlerClass, AIProvider $provider): object
    {
        $handler = Mockery::mock($handlerClass)->shouldIgnoreMissing();
        $handler->shouldReceive('setProvider')->once()->with($provider);

        return $handler;
    }

    private function readProperty(object $object, string $property): mixed
    {
        $reflection = new \ReflectionProperty($object, $property);
        $reflection->setAccessible(true);

        return $reflection->getValue($object);
    }
}
