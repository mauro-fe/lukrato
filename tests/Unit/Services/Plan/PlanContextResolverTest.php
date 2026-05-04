<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Plan;

use Application\Models\Usuario;
use Application\Services\Plan\PlanContext;
use Application\Services\Plan\PlanContextResolver;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class PlanContextResolverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testResolveCachesLastResolvedUserPlan(): void
    {
        $user = $this->createUserMock('pro', 10);
        $calls = 0;

        $resolver = new PlanContextResolver(static function (int $userId) use ($user, &$calls): ?Usuario {
            $calls++;

            return $userId === 10 ? $user : null;
        });

        $first = $resolver->resolve(10);
        $second = $resolver->resolve(10);

        $this->assertInstanceOf(PlanContext::class, $first);
        $this->assertSame($first, $second);
        $this->assertSame(1, $calls);
        $this->assertSame('pro', $resolver->tier(10));
        $this->assertTrue($resolver->isPro(10));
        $this->assertFalse($resolver->isUltra(10));
    }

    public function testResolveRefreshesCacheWhenUserIdChanges(): void
    {
        $proUser = $this->createUserMock('pro', 11);
        $ultraUser = $this->createUserMock('ultra', 12);
        $calls = [];

        $resolver = new PlanContextResolver(static function (int $userId) use ($proUser, $ultraUser, &$calls): ?Usuario {
            $calls[] = $userId;

            return match ($userId) {
                11 => $proUser,
                12 => $ultraUser,
                default => null,
            };
        });

        $this->assertSame('pro', $resolver->tier(11));
        $this->assertSame('ultra', $resolver->tier(12));
        $this->assertTrue($resolver->isUltra(12));
        $this->assertSame([11, 12], $calls);
    }

    public function testResolveReturnsNullAndFreeFlagsWhenUserIsMissing(): void
    {
        $resolver = new PlanContextResolver(static fn(int $userId): ?Usuario => $userId === 99 ? null : throw new \RuntimeException('unexpected user id'));

        $this->assertNull($resolver->resolve(99));
        $this->assertSame('free', $resolver->tier(99));
        $this->assertFalse($resolver->isPro(99));
        $this->assertFalse($resolver->isUltra(99));
    }

    private function createUserMock(string $tier, int $userId, ?bool $isPro = null): Usuario
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
