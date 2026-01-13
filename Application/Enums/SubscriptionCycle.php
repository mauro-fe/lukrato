<?php

namespace Application\Enums;

enum SubscriptionCycle: string
{
    case MONTHLY = 'MONTHLY';
    case YEARLY = 'YEARLY';
    case WEEKLY = 'WEEKLY';

    public static function fromMonths(int $months): self
    {
        return match ($months) {
            12 => self::YEARLY,
            default => self::MONTHLY,
        };
    }
}
