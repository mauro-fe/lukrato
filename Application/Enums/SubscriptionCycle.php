<?php

namespace Application\Enums;

enum SubscriptionCycle: string
{
    case MONTHLY = 'MONTHLY';
    case QUARTERLY = 'QUARTERLY';
    case SEMIANNUALLY = 'SEMIANNUALLY';
    case YEARLY = 'YEARLY';
    case WEEKLY = 'WEEKLY';

    public static function fromMonths(int $months): self
    {
        return match ($months) {
            1 => self::MONTHLY,
            3 => self::QUARTERLY,
            6 => self::SEMIANNUALLY,
            12 => self::YEARLY,
            default => self::MONTHLY,
        };
    }
}
