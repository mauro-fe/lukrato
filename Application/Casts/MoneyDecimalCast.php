<?php

declare(strict_types=1);

namespace Application\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * @implements CastsAttributes<string|null, string|null>
 */
final class MoneyDecimalCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return self::normalize($value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return self::normalize($value);
    }

    public static function normalize(mixed $value, int $scale = 2): ?string
    {
        if ($scale < 0 || $scale > 8) {
            throw new InvalidArgumentException('Escala monetaria invalida.');
        }

        if ($value === null || $value === '') {
            return null;
        }

        if (is_bool($value) || is_array($value) || is_object($value)) {
            throw new InvalidArgumentException('Valor monetario invalido.');
        }

        if (is_int($value)) {
            return self::formatParts((string) abs($value), '', $value < 0, $scale);
        }

        if (is_float($value)) {
            if (!is_finite($value)) {
                throw new InvalidArgumentException('Valor monetario invalido.');
            }

            return self::parseString(sprintf('%.10F', $value), $scale);
        }

        return self::parseString((string) $value, $scale);
    }

    private static function parseString(string $value, int $scale): string
    {
        $normalized = trim($value);
        if ($normalized === '') {
            throw new InvalidArgumentException('Valor monetario invalido.');
        }

        $normalized = str_replace(['R$', 'r$'], '', $normalized);
        $normalized = preg_replace('/\s+/u', '', $normalized) ?? '';

        $negative = false;
        if (str_starts_with($normalized, '-')) {
            $negative = true;
            $normalized = substr($normalized, 1);
        } elseif (str_starts_with($normalized, '+')) {
            $normalized = substr($normalized, 1);
        }

        if ($normalized === '') {
            throw new InvalidArgumentException('Valor monetario invalido.');
        }

        $decimalSeparator = self::detectDecimalSeparator($normalized, $scale);
        if ($decimalSeparator === null) {
            $integer = str_replace([',', '.'], '', $normalized);
            $fraction = '';
        } else {
            $separatorPosition = strrpos($normalized, $decimalSeparator);
            if ($separatorPosition === false) {
                throw new InvalidArgumentException('Valor monetario invalido.');
            }

            $integer = substr($normalized, 0, $separatorPosition);
            $fraction = substr($normalized, $separatorPosition + 1);
            $groupSeparator = $decimalSeparator === ',' ? '.' : ',';
            $integer = str_replace($groupSeparator, '', $integer);
        }

        if ($integer === '') {
            $integer = '0';
        }

        if (!preg_match('/^\d+$/', $integer) || ($fraction !== '' && !preg_match('/^\d+$/', $fraction))) {
            throw new InvalidArgumentException('Valor monetario invalido.');
        }

        return self::formatParts($integer, $fraction, $negative, $scale);
    }

    private static function detectDecimalSeparator(string $value, int $scale): ?string
    {
        $lastComma = strrpos($value, ',');
        $lastDot = strrpos($value, '.');

        if ($lastComma !== false && $lastDot !== false) {
            return $lastComma > $lastDot ? ',' : '.';
        }

        if ($lastComma !== false) {
            return ',';
        }

        if ($lastDot === false) {
            return null;
        }

        $dotCount = substr_count($value, '.');
        $digitsAfterDot = strlen($value) - $lastDot - 1;

        if ($dotCount > 1) {
            return null;
        }

        if ($digitsAfterDot === 3) {
            return null;
        }

        return '.';
    }

    private static function formatParts(string $integer, string $fraction, bool $negative, int $scale): string
    {
        $integer = ltrim($integer, '0');
        if ($integer === '') {
            $integer = '0';
        }

        $fraction = str_pad($fraction, $scale + 1, '0');
        $keptFraction = $scale > 0 ? substr($fraction, 0, $scale) : '';
        $roundDigit = (int) ($fraction[$scale] ?? '0');
        $combined = $integer . $keptFraction;

        if ($roundDigit >= 5) {
            $combined = self::incrementDigits($combined);
        }

        $combined = str_pad($combined, $scale + 1, '0', STR_PAD_LEFT);

        if ($scale === 0) {
            $intPart = ltrim($combined, '0');
            $intPart = $intPart === '' ? '0' : $intPart;
            $isZero = $intPart === '0';

            return ($negative && !$isZero ? '-' : '') . $intPart;
        }

        $intPart = substr($combined, 0, -$scale);
        $fractionPart = substr($combined, -$scale);
        $intPart = ltrim($intPart, '0');
        $intPart = $intPart === '' ? '0' : $intPart;
        $isZero = trim($intPart . $fractionPart, '0') === '';

        return ($negative && !$isZero ? '-' : '') . $intPart . '.' . $fractionPart;
    }

    private static function incrementDigits(string $digits): string
    {
        $carry = 1;
        $result = '';

        for ($i = strlen($digits) - 1; $i >= 0; $i--) {
            $sum = (int) $digits[$i] + $carry;
            if ($sum >= 10) {
                $result = '0' . $result;
                $carry = 1;
                continue;
            }

            $result = (string) $sum . $result;
            $carry = 0;
        }

        return $carry === 1 ? '1' . $result : $result;
    }
}
