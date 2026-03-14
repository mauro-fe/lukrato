<?php

declare(strict_types=1);

namespace Tests\Unit\Services\AI;

use Application\Models\Usuario;
use Application\Services\AI\AIQuotaService;
use Application\Services\Plan\FeatureGate;
use Carbon\Carbon;
use Illuminate\Database\Capsule\Manager as Capsule;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

/**
 * Regressões da quota de IA por plano e por consumo real de LLM.
 */
class AIQuotaServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

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

    public function testCanUseAIReturnsTrueForSupportedPlans(): void
    {
        $this->assertTrue(AIQuotaService::canUseAI($this->createUserMock('free')));
        $this->assertTrue(AIQuotaService::canUseAI($this->createUserMock('pro')));
        $this->assertTrue(AIQuotaService::canUseAI($this->createUserMock('ultra')));
    }

    public function testPaidPlansHaveUnlimitedQuota(): void
    {
        $this->assertTrue(AIQuotaService::hasQuotaRemaining($this->createUserMock('pro')));
        $this->assertTrue(AIQuotaService::hasQuotaRemaining($this->createUserMock('ultra')));
    }

    public function testFreeUserHasQuotaWhenThereIsNoCountedUsage(): void
    {
        $this->requireAiLogsSchema();

        $user = $this->createUserMock('free', $this->newUserId());

        $this->assertTrue(AIQuotaService::hasQuotaRemaining($user));
    }

    public function testGetUsageReturnsBucketedStructure(): void
    {
        $this->requireAiLogsSchema();

        $usage = AIQuotaService::getUsage($this->createUserMock('pro'));

        $this->assertSame(['plan', 'can_use', 'chat', 'categorization'], array_keys($usage));
        $this->assertArrayHasKey('used', $usage['chat']);
        $this->assertArrayHasKey('limit', $usage['chat']);
        $this->assertArrayHasKey('remaining', $usage['chat']);
        $this->assertArrayHasKey('unlimited', $usage['chat']);
        $this->assertArrayHasKey('percentage', $usage['chat']);
        $this->assertArrayHasKey('used', $usage['categorization']);
        $this->assertArrayHasKey('limit', $usage['categorization']);
        $this->assertArrayHasKey('remaining', $usage['categorization']);
        $this->assertArrayHasKey('unlimited', $usage['categorization']);
        $this->assertArrayHasKey('percentage', $usage['categorization']);
    }

    public function testGetUsageForProReturnsUnlimitedBuckets(): void
    {
        $this->requireAiLogsSchema();

        $usage = AIQuotaService::getUsage($this->createUserMock('pro'));

        $this->assertSame('pro', $usage['plan']);
        $this->assertTrue($usage['can_use']);
        $this->assertTrue($usage['chat']['unlimited']);
        $this->assertNull($usage['chat']['limit']);
        $this->assertNull($usage['chat']['remaining']);
        $this->assertTrue($usage['categorization']['unlimited']);
        $this->assertNull($usage['categorization']['limit']);
        $this->assertNull($usage['categorization']['remaining']);
    }

    public function testGetUsageForFreeReturnsLimitedBuckets(): void
    {
        $this->requireAiLogsSchema();

        $usage = AIQuotaService::getUsage($this->createUserMock('free'));

        $this->assertSame('free', $usage['plan']);
        $this->assertTrue($usage['can_use']);
        $this->assertFalse($usage['chat']['unlimited']);
        $this->assertSame(5, $usage['chat']['limit']);
        $this->assertIsInt($usage['chat']['used']);
        $this->assertIsInt($usage['chat']['remaining']);
        $this->assertFalse($usage['categorization']['unlimited']);
        $this->assertSame(5, $usage['categorization']['limit']);
    }

    public function testChatBucketCountsOnlySuccessfulLlmOrTokenUsage(): void
    {
        $this->requireAiLogsSchema();

        $userId = $this->newUserId();
        $user = $this->createUserMock('free', $userId);

        $this->insertAiLog($userId, [
            'type' => 'chat',
            'source' => 'llm',
            'tokens_total' => 32,
            'success' => true,
        ]);
        $this->insertAiLog($userId, [
            'type' => 'chat',
            'source' => 'rule',
            'tokens_total' => 0,
            'success' => true,
        ]);
        $this->insertAiLog($userId, [
            'type' => 'chat',
            'source' => 'computed',
            'tokens_total' => 11,
            'success' => true,
        ]);
        $this->insertAiLog($userId, [
            'type' => 'chat',
            'source' => 'llm',
            'tokens_total' => 19,
            'success' => false,
        ]);
        $this->insertAiLog($userId, [
            'type' => 'suggest_category',
            'source' => 'llm',
            'tokens_total' => 7,
            'success' => true,
        ]);

        $usage = AIQuotaService::getUsage($user);

        $this->assertSame(2, $usage['chat']['used']);
        $this->assertSame(1, $usage['categorization']['used']);
        $this->assertTrue(AIQuotaService::hasQuotaRemaining($user, 'chat'));
    }

    public function testFreeUserRunsOutOfQuotaAfterFiveCountedChatCalls(): void
    {
        $this->requireAiLogsSchema();

        $userId = $this->newUserId();
        $user = $this->createUserMock('free', $userId);

        for ($i = 0; $i < 5; $i++) {
            $this->insertAiLog($userId, [
                'type' => 'chat',
                'source' => 'llm',
                'tokens_total' => 20 + $i,
                'success' => true,
            ]);
        }

        $usage = AIQuotaService::getUsage($user);

        $this->assertSame(5, $usage['chat']['used']);
        $this->assertSame(0, $usage['chat']['remaining']);
        $this->assertFalse(AIQuotaService::hasQuotaRemaining($user, 'chat'));
    }

    public function testFeatureGateResolvesExpectedPlans(): void
    {
        $this->assertSame('free', FeatureGate::planTier($this->createUserMock('free')));
        $this->assertSame('pro', FeatureGate::planTier($this->createUserMock('pro')));
        $this->assertSame('ultra', FeatureGate::planTier($this->createUserMock('ultra')));
    }

    public function testFeatureGateFallsBackToFreeForUnknownCode(): void
    {
        $plan = Mockery::mock();
        $plan->code = 'xpto_desconhecido';

        $user = Mockery::mock(Usuario::class)->makePartial();
        $user->id = 999;
        $user->shouldReceive('planoAtual')->andReturn($plan);
        $user->shouldReceive('isPro')->andReturn(false);

        $this->assertSame('free', FeatureGate::planTier($user));
    }

    public function testBillingConfigHasExpectedAiLimits(): void
    {
        $config = require __DIR__ . '/../../../../Application/Config/Billing.php';

        $this->assertTrue($config['features']['free']['ai_chat']);
        $this->assertSame(5, $config['limits']['free']['ai_messages_per_month']);
        $this->assertNull($config['limits']['pro']['ai_messages_per_month']);
        $this->assertNull($config['limits']['ultra']['ai_messages_per_month']);
    }

    private function requireAiLogsSchema(): void
    {
        try {
            Capsule::connection()->getPdo();
        } catch (\Throwable) {
            $this->markTestSkipped('Requer conexão com banco de dados');
        }

        try {
            $schema = Capsule::schema();
            if (
                !$schema->hasTable('ai_logs')
                || !$schema->hasColumn('ai_logs', 'source')
                || !$schema->hasColumn('ai_logs', 'tokens_total')
            ) {
                $this->markTestSkipped('Tabela ai_logs sem colunas necessárias para a quota atual');
            }
        } catch (\Throwable) {
            $this->markTestSkipped('Não foi possível validar o schema de ai_logs');
        }
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
            'model' => 'gpt-5',
            'tokens_prompt' => 5,
            'tokens_completion' => 5,
            'tokens_total' => 10,
            'response_time_ms' => 50,
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
        return random_int(800000, 899999);
    }

    private function createUserMock(string $tier, int $userId = 1): Usuario
    {
        $plan = Mockery::mock();
        $plan->code = $tier;

        $user = Mockery::mock(Usuario::class)->makePartial();
        $user->id = $userId;
        $user->shouldReceive('planoAtual')->andReturn($tier === 'free' ? null : $plan);
        $user->shouldReceive('isPro')->andReturn(in_array($tier, ['pro', 'ultra'], true));

        return $user;
    }
}
