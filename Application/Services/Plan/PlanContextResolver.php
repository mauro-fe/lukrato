<?php

declare(strict_types=1);

namespace Application\Services\Plan;

use Application\Models\Usuario;
use Closure;

final class PlanContextResolver
{
    private ?int $resolvedUserId = null;
    private ?PlanContext $resolvedPlan = null;

    /** @var Closure(int): (?Usuario) */
    private Closure $userResolver;

    public function __construct(?Closure $userResolver = null)
    {
        $this->userResolver = $userResolver ?? static fn(int $userId): ?Usuario => Usuario::find($userId);
    }

    public function resolve(int $userId): ?PlanContext
    {
        if ($this->resolvedUserId === $userId) {
            return $this->resolvedPlan;
        }

        $this->resolvedUserId = $userId;

        try {
            $user = ($this->userResolver)($userId);
            $this->resolvedPlan = $user?->plan();

            return $this->resolvedPlan;
        } catch (\Throwable) {
            $this->resolvedPlan = null;

            return null;
        }
    }

    public function tier(int $userId): string
    {
        return $this->resolve($userId)?->tier() ?? 'free';
    }

    public function isPro(int $userId): bool
    {
        return $this->resolve($userId)?->isPro() ?? false;
    }

    public function isUltra(int $userId): bool
    {
        return $this->resolve($userId)?->isUltra() ?? false;
    }
}
