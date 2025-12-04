<?php

namespace Application\Formatters;

class DateFormatter
{
    public function parse(?string $value): ?string
    {
        if (empty(trim($value ?? ''))) {
            return null;
        }

        $value = trim($value);

        if (preg_match('~^\d{2}/\d{2}/\d{4}$~', $value)) {
            [$dd, $mm, $yy] = explode('/', $value);

            if (checkdate((int) $mm, (int) $dd, (int) $yy)) {
                return sprintf('%04d-%02d-%02d', $yy, $mm, $dd);
            }

            return null;
        }

        $ts = strtotime($value);
        return $ts ? date('Y-m-d', $ts) : null;
    }


    public function normalize(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_string($value)) {
            $value = trim($value);

            if (preg_match('~^\d{4}-\d{2}-\d{2}$~', $value)) {
                return $value;
            }

            $timestamp = strtotime($value);
            if ($timestamp) {
                return date('Y-m-d', $timestamp);
            }
        }

        return '';
    }
}