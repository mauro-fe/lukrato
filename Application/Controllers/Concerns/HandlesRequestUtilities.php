<?php

declare(strict_types=1);

namespace Application\Controllers\Concerns;

trait HandlesRequestUtilities
{
    protected function getPost(string $key, mixed $default = null): mixed
    {
        return $this->request->post($key, $default);
    }

    protected function getQuery(string $key, mixed $default = null): mixed
    {
        return $this->request->query($key, $default);
    }

    protected function getStringQuery(string $key, string $default = ''): string
    {
        return $this->request->queryString($key, $default);
    }

    protected function getIntQuery(string $key, int $default = 0): int
    {
        return $this->request->queryInt($key, $default);
    }

    protected function getBoolQuery(string $key, bool $default = false): bool
    {
        return $this->request->queryBool($key, $default);
    }

    protected function getArrayQuery(string $key, array $default = []): array
    {
        return $this->request->queryArray($key, $default);
    }

    /**
     * @return array{month:string,year:int,monthNum:int,start:string,end:string}
     */
    protected function parseYearMonth(string $monthInput): array
    {
        $monthInput = trim($monthInput);
        $date = \DateTimeImmutable::createFromFormat('!Y-m', $monthInput);

        if (!$date || $date->format('Y-m') !== $monthInput) {
            throw new \ValueError('Formato de mês inválido (YYYY-MM).');
        }

        return $this->buildYearMonthPeriodFromRequestUtility($date);
    }

    /**
     * @return array{month:string,year:int,monthNum:int,start:string,end:string}
     */
    protected function normalizeYearMonth(string $monthInput, ?string $fallbackMonth = null): array
    {
        $monthInput = trim($monthInput);
        $fallbackMonth ??= date('Y-m');

        $date = \DateTimeImmutable::createFromFormat('!Y-m', $monthInput);
        if ($date && $date->format('Y-m') === $monthInput) {
            return $this->buildYearMonthPeriodFromRequestUtility($date);
        }

        $fallbackDate = \DateTimeImmutable::createFromFormat('!Y-m', $fallbackMonth);
        if ($fallbackDate && $fallbackDate->format('Y-m') === $fallbackMonth) {
            return $this->buildYearMonthPeriodFromRequestUtility($fallbackDate);
        }

        return $this->buildYearMonthPeriodFromRequestUtility(new \DateTimeImmutable('first day of this month'));
    }

    protected function sanitize(string $value): string
    {
        return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
    }

    protected function sanitizeDeep(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map([$this, 'sanitizeDeep'], $value);
        }

        return is_string($value) ? $this->sanitize($value) : $value;
    }

    /**
     * @return array{month:string,year:int,monthNum:int,start:string,end:string}
     */
    private function buildYearMonthPeriodFromRequestUtility(\DateTimeImmutable $date): array
    {
        return [
            'month' => $date->format('Y-m'),
            'year' => (int) $date->format('Y'),
            'monthNum' => (int) $date->format('m'),
            'start' => $date->format('Y-m-01'),
            'end' => $date->format('Y-m-t'),
        ];
    }
}
