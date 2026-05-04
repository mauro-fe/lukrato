<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Lancamento;

use Application\Models\Usuario;
use Application\Services\Lancamento\LancamentoLimitService;
use Application\Services\Plan\PlanContext;
use Application\Services\Plan\PlanContextResolver;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class LancamentoLimitServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testUsageIncludesTierMetadataForFreePlans(): void
    {
        $user = Mockery::mock(Usuario::class)->makePartial();
        $user->id = 10;
        $user->shouldReceive('planoAtual')->once()->andReturn((object) ['code' => 'free']);
        $user->shouldReceive('isPro')->once()->andReturn(false);

        $plan = PlanContext::forUser($user);

        $resolver = new PlanContextResolver(static fn(int $userId): ?Usuario => $userId === 10 ? $user : null);

        $service = Mockery::mock(LancamentoLimitService::class, [$resolver])->makePartial();
        $service->shouldReceive('countUsedInMonth')->once()->with(10, '2026-03')->andReturn(70);

        $usage = $service->usage(10, '2026-03');

        $this->assertSame('free', $usage['plan']);
        $this->assertFalse($usage['is_pro']);
        $this->assertFalse($usage['is_ultra']);
        $this->assertSame('FREE', $usage['plan_label']);
        $this->assertSame('pro', $usage['upgrade_target']);
        $this->assertSame(100, $usage['limit']);
        $this->assertSame(30, $usage['remaining']);
        $this->assertTrue($usage['should_warn']);
        $this->assertFalse($usage['blocked']);
    }

    public function testUsageIncludesTierMetadataForUltraPlans(): void
    {
        $user = Mockery::mock(Usuario::class)->makePartial();
        $user->id = 11;
        $user->shouldReceive('planoAtual')->once()->andReturn((object) ['code' => 'ultra']);
        $user->shouldReceive('isPro')->once()->andReturn(true);

        $plan = PlanContext::forUser($user);

        $resolver = new PlanContextResolver(static fn(int $userId): ?Usuario => $userId === 11 ? $user : null);

        $service = Mockery::mock(LancamentoLimitService::class, [$resolver])->makePartial();
        $service->shouldReceive('countUsedInMonth')->once()->with(11, '2026-03')->andReturn(180);

        $usage = $service->usage(11, '2026-03');

        $this->assertSame('ultra', $usage['plan']);
        $this->assertTrue($usage['is_pro']);
        $this->assertTrue($usage['is_ultra']);
        $this->assertSame('ULTRA', $usage['plan_label']);
        $this->assertNull($usage['upgrade_target']);
        $this->assertNull($usage['limit']);
        $this->assertNull($usage['remaining']);
        $this->assertFalse($usage['should_warn']);
        $this->assertFalse($usage['blocked']);
    }
}
