<?php

declare(strict_types=1);

namespace Tests\Unit\Services\AI;

use Application\DTO\AI\IntentResult;
use Application\Services\AI\AiLogService;
use Carbon\Carbon;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use PHPUnit\Framework\TestCase;

class AiLogServiceTest extends TestCase
{
    private static bool $sqliteFallbackBooted = false;

    /** @var int[] */
    private array $cleanupUserIds = [];

    protected function tearDown(): void
    {
        foreach ($this->cleanupUserIds as $userId) {
            try {
                Capsule::table('ai_logs')->where('user_id', $userId)->delete();
            } catch (\Throwable) {
            }
        }

        $this->cleanupUserIds = [];

        parent::tearDown();
    }

    public function testSummaryUsesLegacyTokenTotalsForCostAndIgnoresZeroTime(): void
    {
        $this->requireAiLogsSchema();

        $before = AiLogService::summary(24);
        $userId = $this->newUserId();

        $this->insertAiLog($userId, [
            'type' => 'image_analysis',
            'model' => 'gpt-4o-mini',
            'tokens_prompt' => 0,
            'tokens_completion' => 0,
            'tokens_total' => 100000,
            'response_time_ms' => 0,
            'source' => null,
            'confidence' => null,
        ]);

        $this->insertAiLog($userId, [
            'type' => 'chat',
            'model' => 'gpt-4o-mini',
            'tokens_prompt' => 100000,
            'tokens_completion' => 50000,
            'tokens_total' => 150000,
            'response_time_ms' => 300,
            'source' => 'llm',
            'confidence' => 0.9,
        ]);

        $after = AiLogService::summary(24);
        $since = Carbon::now()->subHours(24);

        $expectedAvg = (int) Capsule::table('ai_logs')
            ->where('created_at', '>=', $since)
            ->where('response_time_ms', '>', 0)
            ->avg('response_time_ms');

        $expectedCostDelta = 0.0600;

        $this->assertEqualsWithDelta(
            $expectedCostDelta,
            round($after['estimated_cost'] - $before['estimated_cost'], 4),
            0.0001
        );
        $this->assertSame($expectedAvg, $after['avg_time_ms']);
    }

    public function testQualityMetricsCountOnlyLowConfidenceChatAsFallback(): void
    {
        $this->requireAiLogsSchema();

        $userId = $this->newUserId();

        $this->insertAiLog($userId, [
            'type' => 'chat',
            'response_time_ms' => 120,
            'source' => 'llm',
            'confidence' => 0.5,
        ]);

        $this->insertAiLog($userId, [
            'type' => 'chat',
            'response_time_ms' => 80,
            'source' => 'rule',
            'confidence' => 1.0,
            'tokens_total' => 0,
        ]);

        $this->insertAiLog($userId, [
            'type' => 'create_entity',
            'response_time_ms' => 240,
            'source' => 'llm',
            'confidence' => 0.4,
        ]);

        $this->insertAiLog($userId, [
            'type' => 'image_analysis',
            'response_time_ms' => 0,
            'source' => null,
            'confidence' => null,
        ]);

        $since = Carbon::now()->subHours(24);
        $threshold = IntentResult::CONFIDENCE_THRESHOLD;

        $total = Capsule::table('ai_logs')
            ->where('created_at', '>=', $since)
            ->count();

        $lowConfCount = Capsule::table('ai_logs')
            ->where('created_at', '>=', $since)
            ->where('confidence', '>', 0)
            ->where('confidence', '<', $threshold)
            ->count();

        $fallbackCount = Capsule::table('ai_logs')
            ->where('created_at', '>=', $since)
            ->where('type', 'chat')
            ->where('confidence', '>', 0)
            ->where('confidence', '<', $threshold)
            ->count();

        $avgByType = Capsule::table('ai_logs')
            ->where('created_at', '>=', $since)
            ->where('response_time_ms', '>', 0)
            ->select('type')
            ->selectRaw('AVG(response_time_ms) as avg_ms')
            ->groupBy('type')
            ->pluck('avg_ms', 'type')
            ->map(fn($avg) => (int) $avg)
            ->toArray();

        $metrics = AiLogService::qualityMetrics(24);

        $this->assertSame(round(($lowConfCount / $total) * 100, 1), $metrics['low_confidence_rate']);
        $this->assertSame(round(($fallbackCount / $total) * 100, 1), $metrics['fallback_to_chat_rate']);
        $this->assertSame($avgByType['chat'] ?? null, $metrics['avg_response_time_by_type']['chat'] ?? null);
        $this->assertSame($avgByType['create_entity'] ?? null, $metrics['avg_response_time_by_type']['create_entity'] ?? null);
    }

    private function requireAiLogsSchema(): void
    {
        try {
            Capsule::connection()->getPdo();
            $schema = Capsule::schema();
            if (
                $schema->hasTable('ai_logs')
                && $schema->hasColumn('ai_logs', 'tokens_total')
                && $schema->hasColumn('ai_logs', 'confidence')
                && $schema->hasColumn('ai_logs', 'source')
            ) {
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

    private function insertAiLog(int $userId, array $overrides): void
    {
        $this->cleanupUserIds[$userId] = $userId;

        Capsule::table('ai_logs')->insert(array_merge([
            'user_id' => $userId,
            'type' => 'chat',
            'channel' => 'web',
            'prompt' => 'teste',
            'response' => 'ok',
            'provider' => 'openai',
            'model' => 'gpt-4o-mini',
            'tokens_prompt' => 100,
            'tokens_completion' => 50,
            'tokens_total' => 150,
            'response_time_ms' => 100,
            'success' => true,
            'error_message' => null,
            'source' => 'llm',
            'confidence' => 0.9,
            'prompt_version' => 'test-v1',
            'created_at' => Carbon::now(),
        ], $overrides));
    }

    private function newUserId(): int
    {
        return random_int(900000, 999999);
    }
}
