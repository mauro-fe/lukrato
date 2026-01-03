<?php

namespace Application\DTO;

use Carbon\Carbon;

readonly class ReportParameters
{
    public function __construct(
        public Carbon $start,
        public Carbon $end,
        public ?int $accountId = null,
        public ?int $userId = null,
        public bool $includeTransfers = false
    ) {}

    public function useTransfers(): bool
    {
        return $this->includeTransfers || $this->accountId !== null;
    }

    public function isAccountSpecific(): bool
    {
        return $this->accountId !== null;
    }

    public function isGlobal(): bool
    {
        return $this->accountId === null;
    }

    public function getPeriodInDays(): int
    {
        return $this->start->diffInDays($this->end) + 1;
    }

    public function isSingleMonth(): bool
    {
        return $this->start->year === $this->end->year
            && $this->start->month === $this->end->month;
    }

    public function isSingleYear(): bool
    {
        return $this->start->year === $this->end->year;
    }

    public function getPeriodLabel(): string
    {
        if ($this->isSingleMonth()) {
            return $this->start->format('m/Y');
        }

        if ($this->isSingleYear()) {
            return (string)$this->start->year;
        }

        return sprintf(
            '%s - %s',
            $this->start->format('d/m/Y'),
            $this->end->format('d/m/Y')
        );
    }

    public static function forMonth(int $year, int $month, ?int $accountId = null, ?int $userId = null): self
    {
        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end = (clone $start)->endOfMonth()->endOfDay();

        return new self($start, $end, $accountId, $userId);
    }

    public static function forYear(int $year, ?int $accountId = null, ?int $userId = null): self
    {
        $start = Carbon::create($year, 1, 1)->startOfDay();
        $end = (clone $start)->endOfYear()->endOfDay();

        return new self($start, $end, $accountId, $userId);
    }

    public static function forPeriod(Carbon $start, Carbon $end, ?int $accountId = null, ?int $userId = null): self
    {
        return new self(
            (clone $start)->startOfDay(),
            (clone $end)->endOfDay(),
            $accountId,
            $userId
        );
    }

    public function withTransfers(bool $includeTransfers): self
    {
        return new self(
            $this->start,
            $this->end,
            $this->accountId,
            $this->userId,
            $includeTransfers
        );
    }

    public function forAccount(?int $accountId): self
    {
        return new self(
            $this->start,
            $this->end,
            $accountId,
            $this->userId,
            $this->includeTransfers
        );
    }
}
