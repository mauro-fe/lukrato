<?php

namespace Application\Lib;

use Cocur\Slugify\Slugify;
use Illuminate\Database\Eloquent\Model;

class Helpers
{
    private static ?Slugify $slugifyInstance = null;

    public static function slugify(string $text): string
    {
        if ($text === '') return 'slug';
        self::$slugifyInstance ??= new Slugify();
        return self::$slugifyInstance->slugify($text);
    }

    public static function slugifyUnique(string $baseName, int $ownerId, string $modelClass, string $slugColumn = 'slug', string $ownerColumn = 'user_id'): string
    {
        $slugBase = self::slugify($baseName);
        $slug = $slugBase;
        $i = 1;

        while ($modelClass::where($slugColumn, $slug)->where($ownerColumn, $ownerId)->exists()) {
            $slug = "{$slugBase}-{$i}";
            $i++;
        }
        return $slug;
    }

    public static function formatErrorHtml(string|array $message): string
    {
        $html = '';
        if (is_array($message)) {
            foreach ($message as $m) {
                $html .= '<div class="alert alert-danger mb-2">' . htmlspecialchars((string)$m, ENT_QUOTES, 'UTF-8') . '</div>';
            }
            return $html;
        }
        return '<div class="alert alert-danger">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</div>';
    }

    public static function escapeHtml(string|array|null $value): string|array|null
    {
        if (is_array($value)) return array_map([self::class, 'escapeHtml'], $value);
        return is_string($value) ? htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8') : $value;
    }

    public static function generateToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }

    public static function baseUrl(string $path = ''): string
    {
        $base = rtrim((string) (defined('BASE_URL') ? BASE_URL : ''), '/');
        return $base . '/' . ltrim($path, '/');
    }

    public static function formatMoneyBRL(float|int $amount): string
    {
        return 'R$ ' . number_format((float)$amount, 2, ',', '.');
    }

    public static function parseMoney(string $raw): float
    {
        $s = trim($raw);
        if (preg_match('/^[\d\.\s]+,\d{1,2}$/', $s)) {
            $s = str_replace(['.', ' '], '', $s);
            $s = str_replace(',', '.', $s);
        }
        return (float) preg_replace('/[^\d\.-]/', '', $s);
    }

    public static function isValidCpf(string $cpf): bool
    {
        $cpf = preg_replace('/\D/', '', $cpf ?? '');
        if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) return false;

        $sum = 0;
        for ($i = 0, $weight = 10; $i < 9; $i++, $weight--) $sum += (int)$cpf[$i] * $weight;
        $d1 = ($sum * 10) % 11;
        if ($d1 === 10) $d1 = 0;
        if ((int)$cpf[9] !== $d1) return false;

        $sum = 0;
        for ($i = 0, $weight = 11; $i < 10; $i++, $weight--) $sum += (int)$cpf[$i] * $weight;
        $d2 = ($sum * 10) % 11;
        if ($d2 === 10) $d2 = 0;

        return (int)$cpf[10] === $d2;
    }

    public static function isValidCnpj(string $cnpj): bool
    {
        $cnpj = preg_replace('/\D/', '', $cnpj ?? '');
        if (strlen($cnpj) !== 14 || preg_match('/^(\d)\1{13}$/', $cnpj)) return false;

        $w1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $w2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

        $sum = 0;
        for ($i = 0; $i < 12; $i++) $sum += (int)$cnpj[$i] * $w1[$i];
        $r = $sum % 11;
        $d1 = ($r < 2) ? 0 : 11 - $r;
        if ((int)$cnpj[12] !== $d1) return false;

        $sum = 0;
        for ($i = 0; $i < 13; $i++) $sum += (int)$cnpj[$i] * $w2[$i];
        $r = $sum % 11;
        $d2 = ($r < 2) ? 0 : 11 - $r;

        return (int)$cnpj[13] === $d2;
    }
}
