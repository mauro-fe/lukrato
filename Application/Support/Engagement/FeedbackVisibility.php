<?php

declare(strict_types=1);

namespace Application\Support\Engagement;

use DateTimeImmutable;
use DateTimeInterface;
use Throwable;

final class FeedbackVisibility
{
    public const MIN_ACCOUNT_AGE_DAYS = 7;

    public static function minimumAccountAgeDays(): int
    {
        return self::MIN_ACCOUNT_AGE_DAYS;
    }

    public static function resolveDateTime(mixed $value): ?DateTimeImmutable
    {
        if ($value instanceof DateTimeImmutable) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($value);
        }

        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return new DateTimeImmutable($value);
        } catch (Throwable) {
            return null;
        }
    }

    public static function availableAt(mixed $value, ?int $minimumAccountAgeDays = null): ?DateTimeImmutable
    {
        $createdAt = self::resolveDateTime($value);
        if ($createdAt === null) {
            return null;
        }

        $days = $minimumAccountAgeDays ?? self::minimumAccountAgeDays();

        return $createdAt->modify(sprintf('+%d days', $days));
    }

    public static function canCollectGeneralFeedback(
        mixed $value,
        ?DateTimeInterface $now = null,
        ?int $minimumAccountAgeDays = null
    ): bool {
        $availableAt = self::availableAt($value, $minimumAccountAgeDays);
        if ($availableAt === null) {
            return true;
        }

        $reference = $now instanceof DateTimeImmutable
            ? $now
            : ($now instanceof DateTimeInterface
                ? DateTimeImmutable::createFromInterface($now)
                : new DateTimeImmutable('now'));

        return $availableAt <= $reference;
    }
}
