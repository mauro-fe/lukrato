<?php

declare(strict_types=1);

namespace Tests\Unit\Services\AI;

use Application\Config\AiRuntimeConfig;
use Application\Container\ApplicationContainer;
use Application\DTO\AI\AIRequestDTO;
use Application\DTO\AI\AIResponseDTO;
use Application\Enums\AI\AIChannel;
use Application\Enums\AI\IntentType;
use Application\Services\AI\AIService;
use Application\Services\AI\AiLogService;
use Application\Services\AI\Contracts\AIProvider;
use Application\Services\AI\Handlers\CategorizationHandler;
use Application\Services\AI\Handlers\ChatHandlerV2;
use Application\Services\AI\Handlers\ConfirmationHandler;
use Application\Services\AI\Handlers\EntityCreationHandler;
use Application\Services\AI\Handlers\FinancialAnalysisHandler;
use Application\Services\AI\Handlers\PayFaturaHandler;
use Application\Services\AI\Handlers\QuickQueryHandler;
use Application\Services\AI\Handlers\TransactionExtractorHandler;
use Application\Services\AI\IntentRouter;
use Application\Services\Infrastructure\CacheService;
use Carbon\Carbon;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Container\Container as IlluminateContainer;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class AIServiceLoggingTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private static bool $sqliteFallbackBooted = false;

    protected function setUp(): void
    {
        parent::setUp();
        ApplicationContainer::flush();
        $this->requireAiLogsSchema();
        $this->resetAiLogTableExistsCache();
    }

    protected function tearDown(): void
    {
        try {
            Capsule::table('ai_logs')->delete();
        } catch (\Throwable) {
        }

        ApplicationContainer::flush();
        parent::tearDown();
    }

    public function testLogDispatchPersistsInternalFailureMessageInsteadOfFriendlyFallback(): void
    {
        $provider = Mockery::mock(AIProvider::class);
        $provider->shouldReceive('getLastMeta')->andReturn([]);
        $provider->shouldReceive('getModel')->andReturn('gpt-4o-mini');

        $container = new IlluminateContainer();
        foreach (
            [
                ChatHandlerV2::class,
                QuickQueryHandler::class,
                CategorizationHandler::class,
                TransactionExtractorHandler::class,
                FinancialAnalysisHandler::class,
                EntityCreationHandler::class,
                ConfirmationHandler::class,
                PayFaturaHandler::class,
            ] as $handlerClass
        ) {
            $handler = Mockery::mock($handlerClass);
            $handler->shouldReceive('setProvider')->once()->with($provider);
            $container->instance($handlerClass, $handler);
        }

        ApplicationContainer::setInstance($container);

        $service = new AIService(
            provider: $provider,
            cache: Mockery::mock(CacheService::class),
            intentRouter: Mockery::mock(IntentRouter::class),
            runtimeConfig: new AiRuntimeConfig(),
        );

        $request = new AIRequestDTO(
            userId: 991001,
            message: 'teste de falha',
            channel: AIChannel::WEB,
        );
        $response = AIResponseDTO::failWithInternalError(
            'O assistente de IA esta indisponivel no momento. Tente novamente em instantes.',
            'RuntimeException: cURL error 6: Could not resolve host: api.openai.com',
            IntentType::CHAT,
        );

        $method = new \ReflectionMethod($service, 'logDispatch');
        $method->setAccessible(true);
        $method->invoke($service, $request, $response, IntentType::CHAT, 0.123, 0.82);

        $log = Capsule::table('ai_logs')
            ->where('user_id', 991001)
            ->orderByDesc('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame(
            'RuntimeException: cURL error 6: Could not resolve host: api.openai.com',
            $log->error_message
        );
        $this->assertSame(
            'O assistente de IA esta indisponivel no momento. Tente novamente em instantes.',
            $log->response
        );
    }

    private function requireAiLogsSchema(): void
    {
        try {
            Capsule::connection()->getPdo();
            if (Capsule::schema()->hasTable('ai_logs')) {
                return;
            }
        } catch (\Throwable) {
        }

        $this->bootSqliteFallback();
    }

    private function bootSqliteFallback(): void
    {
        if (self::$sqliteFallbackBooted) {
            return;
        }

        $capsule = new Capsule();
        $capsule->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        Capsule::schema()->create('ai_logs', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('user_id')->nullable();
            $table->string('type', 50)->nullable();
            $table->string('channel', 20)->nullable();
            $table->text('prompt')->nullable();
            $table->text('response')->nullable();
            $table->string('provider', 50)->nullable();
            $table->string('model', 100)->nullable();
            $table->unsignedInteger('tokens_prompt')->nullable();
            $table->unsignedInteger('tokens_completion')->nullable();
            $table->unsignedInteger('tokens_total')->nullable();
            $table->unsignedInteger('response_time_ms')->default(0);
            $table->boolean('success')->default(true);
            $table->text('error_message')->nullable();
            $table->string('source', 20)->nullable();
            $table->float('confidence')->nullable();
            $table->string('prompt_version', 20)->nullable();
            $table->timestamp('created_at')->nullable();
        });

        self::$sqliteFallbackBooted = true;
    }

    private function resetAiLogTableExistsCache(): void
    {
        $reflection = new \ReflectionProperty(AiLogService::class, 'tableExists');
        $reflection->setAccessible(true);
        $reflection->setValue(null, null);

        Capsule::table('ai_logs')->where('created_at', '<=', Carbon::now())->delete();
    }
}
