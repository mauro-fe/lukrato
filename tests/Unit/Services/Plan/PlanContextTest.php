<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Plan;

use Application\Models\Usuario;
use Application\Services\Plan\PlanContext;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class PlanContextTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testTierAndFlagsRespectConfiguredOrder(): void
    {
        $free = PlanContext::forUser($this->createUserMock('free'));
        $pro = PlanContext::forUser($this->createUserMock('pro'));
        $ultra = PlanContext::forUser($this->createUserMock('ultra'));

        $this->assertSame('free', $free->tier());
        $this->assertTrue($free->isFree());
        $this->assertFalse($free->isPro());

        $this->assertSame('pro', $pro->tier());
        $this->assertTrue($pro->isPro());
        $this->assertFalse($pro->isUltra());
        $this->assertTrue($pro->atLeast('pro'));
        $this->assertFalse($pro->atLeast('ultra'));

        $this->assertSame('ultra', $ultra->tier());
        $this->assertTrue($ultra->isUltra());
        $this->assertTrue($ultra->atLeast('pro'));
        $this->assertTrue($ultra->atLeast('ultra'));
    }

    public function testLabelsAndUpgradeTargetsComeFromTierMetadata(): void
    {
        $free = PlanContext::forUser($this->createUserMock('free'));
        $pro = PlanContext::forUser($this->createUserMock('pro'));
        $ultra = PlanContext::forUser($this->createUserMock('ultra'));

        $this->assertSame('FREE', $free->label());
        $this->assertSame('pro', $free->upgradeTarget());

        $this->assertSame('PRO', $pro->label());
        $this->assertSame('ultra', $pro->upgradeTarget());

        $this->assertSame('ULTRA', $ultra->label());
        $this->assertNull($ultra->upgradeTarget());
    }

    public function testSummarySerializesConsistentPlanMetadata(): void
    {
        $pro = PlanContext::forUser($this->createUserMock('pro'));
        $ultra = PlanContext::forUser($this->createUserMock('ultra'));

        $this->assertSame([
            'plan' => 'pro',
            'is_pro' => true,
            'is_ultra' => false,
            'plan_label' => 'PRO',
            'upgrade_target' => 'ultra',
        ], $pro->summary());

        $this->assertSame([
            'plan_tier' => 'ultra',
            'is_pro' => true,
            'is_ultra' => true,
            'plan_label' => 'ULTRA',
            'upgrade_target' => null,
        ], $ultra->summary('plan_tier'));

        $this->assertSame([
            'plan' => 'free',
            'is_pro' => false,
            'is_ultra' => false,
            'plan_label' => 'FREE',
            'upgrade_target' => 'pro',
        ], PlanContext::summaryForTier('free'));
    }

    public function testAllowsAndLimitResolveFromTierConfig(): void
    {
        $free = PlanContext::forUser($this->createUserMock('free'));
        $ultra = PlanContext::forUser($this->createUserMock('ultra'));

        $this->assertFalse($free->allows('reports'));
        $this->assertTrue($ultra->allows('previsao_saldo'));
        $this->assertSame(5, $free->limit('ai_messages_per_month'));
        $this->assertNull($ultra->limit('ai_messages_per_month'));
    }

    public function testUltraCodeFallsBackToFreeWithoutPaidAccess(): void
    {
        $inactiveUltra = PlanContext::forUser($this->createUserMock('ultra', 99, false));

        $this->assertSame('free', $inactiveUltra->tier());
        $this->assertTrue($inactiveUltra->isFree());
        $this->assertFalse($inactiveUltra->isPro());
        $this->assertSame('pro', $inactiveUltra->upgradeTarget());
    }

    private function createUserMock(string $tier, int $userId = 1, ?bool $isPro = null): Usuario
    {
        $plan = Mockery::mock();
        $plan->code = $tier;

        $user = Mockery::mock(Usuario::class)->makePartial();
        $user->id = $userId;
        $user->shouldReceive('planoAtual')->andReturn($tier === 'free' ? null : $plan);
        $user->shouldReceive('isPro')->andReturn($isPro ?? in_array($tier, ['pro', 'ultra'], true));

        return $user;
    }
}
