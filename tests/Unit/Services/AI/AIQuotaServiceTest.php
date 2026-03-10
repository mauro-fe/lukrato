<?php

declare(strict_types=1);

namespace Tests\Unit\Services\AI;

use Application\Models\Usuario;
use Application\Services\AI\AIQuotaService;
use Application\Services\Plan\FeatureGate;
use PHPUnit\Framework\TestCase;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

/**
 * Testes para o sistema de quota de IA por plano.
 *
 * Verifica:
 * - canUseAI() retorna true/false conforme a feature ai_chat do plano
 * - hasQuotaRemaining() respeita limites por plano
 * - getUsage() retorna estrutura correta por tier
 */
class AIQuotaServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private function skipIfNoDb(): void
    {
        try {
            \Illuminate\Database\Capsule\Manager::connection()->getPdo();
        } catch (\Throwable) {
            $this->markTestSkipped('Requer conexão com banco de dados');
        }
    }

    // ─── canUseAI ──────────────────────────────────────────

    public function testCanUseAIReturnsTrueForFreeUser(): void
    {
        // Billing.php: free.ai_chat = true (degustação com 5 msgs)
        $user = $this->createUserMock('free');

        // canUseAI depende de FeatureGate::allows que lê Billing.php
        // Free tem ai_chat=true na config
        $result = AIQuotaService::canUseAI($user);
        $this->assertTrue($result, 'Free user deve ter ai_chat=true (degustação)');
    }

    public function testCanUseAIReturnsTrueForProUser(): void
    {
        $user = $this->createUserMock('pro');
        $this->assertTrue(AIQuotaService::canUseAI($user));
    }

    public function testCanUseAIReturnsTrueForUltraUser(): void
    {
        $user = $this->createUserMock('ultra');
        $this->assertTrue(AIQuotaService::canUseAI($user));
    }

    // ─── hasQuotaRemaining ─────────────────────────────────

    public function testProUserHasUnlimitedQuota(): void
    {
        $user = $this->createUserMock('pro');

        // Pro: ai_messages_per_month = null (ilimitado)
        // limit() retorna null → hasQuotaRemaining retorna true
        $result = AIQuotaService::hasQuotaRemaining($user);
        $this->assertTrue($result, 'Pro user deve ter quota ilimitada');
    }

    public function testUltraUserHasUnlimitedQuota(): void
    {
        $user = $this->createUserMock('ultra');
        $result = AIQuotaService::hasQuotaRemaining($user);
        $this->assertTrue($result, 'Ultra user deve ter quota ilimitada');
    }

    public function testFreeUserQuotaCheckRequiresDb(): void
    {
        $this->skipIfNoDb();

        $user = $this->createUserMock('free');
        // Com DB, sem mensagens, deve ter quota restante (0 < 5)
        $result = AIQuotaService::hasQuotaRemaining($user);
        $this->assertTrue($result, 'Free user com 0 msgs deve ter quota restante');
    }

    // ─── getUsage ──────────────────────────────────────────

    public function testGetUsageForProReturnsUnlimited(): void
    {
        $this->skipIfNoDb();

        $user = $this->createUserMock('pro');
        $usage = AIQuotaService::getUsage($user);

        $this->assertIsArray($usage);
        $this->assertEquals('pro', $usage['plan']);
        $this->assertTrue($usage['can_use']);
        $this->assertTrue($usage['unlimited']);
        $this->assertNull($usage['limit']);
        $this->assertNull($usage['remaining']);
    }

    public function testGetUsageForUltraReturnsUnlimited(): void
    {
        $this->skipIfNoDb();

        $user = $this->createUserMock('ultra');
        $usage = AIQuotaService::getUsage($user);

        $this->assertIsArray($usage);
        $this->assertEquals('ultra', $usage['plan']);
        $this->assertTrue($usage['unlimited']);
    }

    public function testGetUsageForFreeReturnsLimited(): void
    {
        $this->skipIfNoDb();

        $user = $this->createUserMock('free');
        $usage = AIQuotaService::getUsage($user);

        $this->assertIsArray($usage);
        $this->assertEquals('free', $usage['plan']);
        $this->assertTrue($usage['can_use']);
        $this->assertFalse($usage['unlimited']);
        $this->assertEquals(5, $usage['limit']);
        $this->assertIsInt($usage['used']);
        $this->assertIsInt($usage['remaining']);
    }

    // ─── getUsage structure ────────────────────────────────

    public function testGetUsageReturnsExpectedKeys(): void
    {
        $this->skipIfNoDb();

        $user = $this->createUserMock('pro');
        $usage = AIQuotaService::getUsage($user);

        $expectedKeys = ['plan', 'can_use', 'used', 'limit', 'remaining', 'unlimited', 'percentage'];
        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $usage, "Chave '{$key}' deve existir no getUsage()");
        }
    }

    // ─── FeatureGate planTier ──────────────────────────────

    public function testFeatureGateResolvesFreeTier(): void
    {
        $user = $this->createUserMock('free');
        $this->assertEquals('free', FeatureGate::planTier($user));
    }

    public function testFeatureGateResolvesProTier(): void
    {
        $user = $this->createUserMock('pro');
        $this->assertEquals('pro', FeatureGate::planTier($user));
    }

    public function testFeatureGateResolvesUltraTier(): void
    {
        $user = $this->createUserMock('ultra');
        $this->assertEquals('ultra', FeatureGate::planTier($user));
    }

    public function testFeatureGateFallsBackToFreeForUnknownCode(): void
    {
        $plan = Mockery::mock();
        $plan->code = 'xpto_desconhecido';

        $user = Mockery::mock(Usuario::class)->makePartial();
        $user->id = 999;
        $user->shouldReceive('planoAtual')->andReturn($plan);
        $user->shouldReceive('isPro')->andReturn(false);

        $this->assertEquals('free', FeatureGate::planTier($user));
    }

    // ─── Billing config integrity ──────────────────────────

    public function testBillingConfigHasAllPlans(): void
    {
        $config = require __DIR__ . '/../../../../Application/Config/Billing.php';

        $this->assertArrayHasKey('limits', $config);
        $this->assertArrayHasKey('features', $config);

        foreach (['free', 'pro', 'ultra'] as $plan) {
            $this->assertArrayHasKey($plan, $config['limits'], "Plano '{$plan}' deve existir em limits");
            $this->assertArrayHasKey($plan, $config['features'], "Plano '{$plan}' deve existir em features");
        }
    }

    public function testBillingConfigFreeHasAiChatEnabled(): void
    {
        $config = require __DIR__ . '/../../../../Application/Config/Billing.php';
        $this->assertTrue($config['features']['free']['ai_chat'], 'Free deve ter ai_chat=true (degustação)');
    }

    public function testBillingConfigFreeHas5MessageLimit(): void
    {
        $config = require __DIR__ . '/../../../../Application/Config/Billing.php';
        $this->assertEquals(5, $config['limits']['free']['ai_messages_per_month']);
    }

    public function testBillingConfigProHasUnlimitedMessages(): void
    {
        $config = require __DIR__ . '/../../../../Application/Config/Billing.php';
        $this->assertNull($config['limits']['pro']['ai_messages_per_month']);
    }

    public function testBillingConfigUltraHasUnlimitedMessages(): void
    {
        $config = require __DIR__ . '/../../../../Application/Config/Billing.php';
        $this->assertNull($config['limits']['ultra']['ai_messages_per_month']);
    }

    // ─── Helpers ───────────────────────────────────────────

    /**
     * Cria um mock de Usuario com o plano especificado.
     */
    private function createUserMock(string $tier): Usuario
    {
        $plan = Mockery::mock();
        $plan->code = $tier;

        $user = Mockery::mock(Usuario::class)->makePartial();
        $user->id = 1;
        $user->shouldReceive('planoAtual')->andReturn($tier === 'free' ? null : $plan);
        $user->shouldReceive('isPro')->andReturn(in_array($tier, ['pro', 'ultra']));

        return $user;
    }
}
