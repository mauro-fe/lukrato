<?php

declare(strict_types=1);

namespace Application\Services\Importacao;

final class ImportSanitizer
{
    public static function sanitizeText(?string $value, int $maxLength = 0, bool $preserveNewlines = false): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", (string) ($value ?? ''));
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/u', '', $text) ?? '';

        if ($preserveNewlines) {
            $text = preg_replace('/\t+/u', ' ', $text) ?? $text;
            $text = preg_replace('/\n{3,}/u', "\n\n", $text) ?? $text;
        } else {
            $text = preg_replace('/[\n\t]+/u', ' ', $text) ?? $text;
        }

        $text = preg_replace('/ {2,}/u', ' ', $text) ?? $text;
        $text = trim($text);

        if ($maxLength > 0) {
            return mb_substr($text, 0, $maxLength);
        }

        return $text;
    }

    public static function sanitizeFilename(?string $filename, string $fallback = 'importacao.dat'): string
    {
        $filename = str_replace('\\', '/', (string) ($filename ?? ''));
        $filename = basename($filename);
        $filename = self::sanitizeText($filename, 255);
        $filename = preg_replace('/[<>:"\/\\|?*]+/u', '_', $filename) ?? '';
        $filename = trim($filename, ". \t\n\r\0\x0B");

        if ($filename === '') {
            $filename = self::sanitizeText($fallback, 255);
        }

        return mb_substr($filename, 0, 255);
    }

    public static function sanitizeMixed(mixed $value): mixed
    {
        if (is_string($value)) {
            return self::sanitizeText($value, 0, true);
        }

        if (is_array($value)) {
            $sanitized = [];
            foreach ($value as $key => $item) {
                $sanitized[$key] = self::sanitizeMixed($item);
            }

            return $sanitized;
        }

        return $value;
    }
}
