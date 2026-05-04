<?php

declare(strict_types=1);

namespace Application\Services\Importacao\Parsers;

use Application\DTO\Importacao\ImportProfileConfigDTO;
use Application\DTO\Importacao\NormalizedImportRowDTO;
use Application\Services\Importacao\Contracts\ImportParserInterface;
use Application\Services\Importacao\ImportSanitizer;
use Application\Services\Importacao\ImportSecurityPolicy;

class OfxImportParser implements ImportParserInterface
{
    public function supports(string $sourceType): bool
    {
        return strtolower(trim($sourceType)) === 'ofx';
    }

    public function parse(string $contents, ImportProfileConfigDTO $profile): array
    {
        $contents = $this->normalizeContentsEncoding($contents);
        $trimmed = trim($contents);
        if ($trimmed === '') {
            return [];
        }

        $normalized = strtoupper($trimmed);
        if (!str_contains($normalized, '<OFX') && !str_contains($normalized, '<STMTTRN')) {
            throw new \InvalidArgumentException('Arquivo OFX inválido para importação.');
        }

        $rows = [];
        foreach ($this->extractTransactionBlocks($trimmed) as $block) {
            $row = $this->buildRow($block);
            if ($row instanceof NormalizedImportRowDTO) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /**
     * @return array<int, string>
     */
    private function extractTransactionBlocks(string $contents): array
    {
        $limit = ImportSecurityPolicy::maxRowsPerFile();
        $matches = [];
        preg_match_all('/<STMTTRN>(.*?)<\/STMTTRN>/is', $contents, $matches);
        if (!empty($matches[1])) {
            if (count($matches[1]) > $limit) {
                throw new \InvalidArgumentException(ImportSecurityPolicy::rowsLimitMessage($limit));
            }

            return $matches[1];
        }

        $segments = preg_split('/<STMTTRN>/i', $contents) ?: [];
        if (count($segments) <= 1) {
            return [];
        }

        $blocks = [];
        foreach (array_slice($segments, 1) as $segment) {
            $segment = preg_split('/<\/STMTTRN>/i', $segment, 2)[0] ?? '';
            $segment = trim($segment);
            if ($segment !== '') {
                if (count($blocks) >= $limit) {
                    throw new \InvalidArgumentException(ImportSecurityPolicy::rowsLimitMessage($limit));
                }

                $blocks[] = $segment;
            }
        }

        return $blocks;
    }

    private function buildRow(string $block): ?NormalizedImportRowDTO
    {
        $rawAmount = $this->extractField($block, 'TRNAMT');
        $rawDate = $this->extractField($block, 'DTPOSTED');

        if ($rawAmount === null || $rawDate === null) {
            return null;
        }

        $amount = $this->normalizeAmount($rawAmount);
        if ($amount === null || abs($amount) <= 0.0) {
            return null;
        }

        $date = $this->normalizeDate($rawDate);
        if ($date === null) {
            return null;
        }

        $name = $this->extractField($block, 'NAME');
        $memo = $this->extractField($block, 'MEMO');
        $description = ImportSanitizer::sanitizeText((string) ($name ?? $memo ?? 'Lançamento OFX'), 190);

        $fitId = ImportSanitizer::sanitizeText((string) ($this->extractField($block, 'FITID') ?? ''), 120);
        $memo = ImportSanitizer::sanitizeText((string) ($memo ?? ''), 500, true);
        $trnType = strtolower(trim((string) ($this->extractField($block, 'TRNTYPE') ?? '')));

        $type = $amount >= 0 ? 'receita' : 'despesa';
        if ($amount == 0.0 && in_array($trnType, ['debit', 'payment', 'fee'], true)) {
            $type = 'despesa';
        }

        return NormalizedImportRowDTO::fromArray([
            'date' => $date,
            'amount' => abs($amount),
            'type' => $type,
            'description' => $description,
            'memo' => $memo !== '' ? $memo : null,
            'external_id' => $fitId !== '' ? $fitId : null,
            'raw' => [
                'trntype' => $trnType,
                'raw_amount' => $rawAmount,
                'raw_date' => $rawDate,
            ],
        ]);
    }

    private function extractField(string $block, string $tag): ?string
    {
        $patternInline = sprintf('/<%s>\s*([^\r\n<]+)/i', preg_quote($tag, '/'));
        if (preg_match($patternInline, $block, $matches) === 1) {
            $value = trim((string) ($matches[1] ?? ''));
            return $value !== '' ? $value : null;
        }

        $patternWrapped = sprintf('/<%s>\s*(.*?)\s*<\/%s>/is', preg_quote($tag, '/'), preg_quote($tag, '/'));
        if (preg_match($patternWrapped, $block, $matches) === 1) {
            $value = trim((string) ($matches[1] ?? ''));
            return $value !== '' ? $value : null;
        }

        return null;
    }

    private function normalizeAmount(string $raw): ?float
    {
        $trimmed = trim($raw);
        if ($trimmed === '') {
            return null;
        }

        $signal = str_contains($trimmed, '-') ? -1.0 : 1.0;
        $sanitized = preg_replace('/[^0-9,\.]/', '', $trimmed) ?? '';
        if ($sanitized === '') {
            return null;
        }

        $decimalSeparator = $this->detectDecimalSeparator($sanitized);
        if ($decimalSeparator !== null) {
            $position = strrpos($sanitized, $decimalSeparator);
            if ($position === false) {
                return null;
            }

            $integerPart = preg_replace('/[,.]/', '', substr($sanitized, 0, $position)) ?? '';
            $fractionPart = preg_replace('/[,.]/', '', substr($sanitized, $position + 1)) ?? '';
            if ($fractionPart === '') {
                return null;
            }

            $normalized = ($integerPart !== '' ? $integerPart : '0') . '.' . $fractionPart;
        } else {
            $normalized = preg_replace('/[,.]/', '', $sanitized) ?? '';
        }

        if ($normalized === '' || !is_numeric($normalized)) {
            return null;
        }

        return round((float) $normalized * $signal, 2);
    }

    private function detectDecimalSeparator(string $value): ?string
    {
        $lastComma = strrpos($value, ',');
        $lastDot = strrpos($value, '.');

        if ($lastComma !== false && $lastDot !== false) {
            return $lastComma > $lastDot ? ',' : '.';
        }

        $separator = $lastComma !== false ? ',' : ($lastDot !== false ? '.' : null);
        if ($separator === null) {
            return null;
        }

        $position = strrpos($value, $separator);
        if ($position === false) {
            return null;
        }

        $digitsAfter = strlen($value) - $position - 1;
        if ($digitsAfter > 0 && $digitsAfter <= 2) {
            return $separator;
        }

        return null;
    }

    private function normalizeContentsEncoding(string $contents): string
    {
        if ($contents === '') {
            return '';
        }

        if ($this->isValidUtf8($contents)) {
            return $contents;
        }

        $declaredEncoding = $this->detectDeclaredEncoding($contents);
        $candidates = array_values(array_unique(array_filter([
            $declaredEncoding,
            'Windows-1252',
            'ISO-8859-1',
            'UTF-8',
            'ASCII',
        ])));

        foreach ($candidates as $candidate) {
            $converted = $this->tryConvertToUtf8($contents, (string) $candidate);
            if ($converted === null) {
                continue;
            }

            if ($this->isValidUtf8($converted)) {
                return $converted;
            }
        }

        return $contents;
    }

    private function detectDeclaredEncoding(string $contents): ?string
    {
        if (preg_match('/^\s*CHARSET\s*:\s*([^\r\n]+)/mi', $contents, $matches) === 1) {
            return $this->normalizeDeclaredEncodingToken($matches[1]);
        }

        if (preg_match('/<\?xml[^>]*encoding=["\']([^"\']+)["\']/i', $contents, $matches) === 1) {
            return $this->normalizeDeclaredEncodingToken($matches[1]);
        }

        return null;
    }

    private function normalizeDeclaredEncodingToken(string $value): ?string
    {
        $token = strtoupper(trim($value));
        $token = preg_replace('/[^A-Z0-9\-_]/', '', $token) ?? '';

        if ($token === '') {
            return null;
        }

        return match ($token) {
            '1252', 'CP1252', 'WINDOWS-1252', 'WINDOWS1252' => 'Windows-1252',
            'UTF8', 'UTF-8' => 'UTF-8',
            'USASCII', 'ASCII' => 'ASCII',
            '8859-1', 'ISO-8859-1', 'ISO8859-1', 'LATIN1' => 'ISO-8859-1',
            default => $token,
        };
    }

    private function tryConvertToUtf8(string $contents, string $sourceEncoding): ?string
    {
        $encoding = trim($sourceEncoding);
        if ($encoding === '') {
            return null;
        }

        if (strtoupper($encoding) === 'UTF-8') {
            return $this->isValidUtf8($contents) ? $contents : null;
        }

        if (function_exists('mb_convert_encoding')) {
            $converted = @mb_convert_encoding($contents, 'UTF-8', $encoding);
            if (is_string($converted) && $converted !== '') {
                return $converted;
            }
        }

        if (function_exists('iconv')) {
            $converted = @iconv($encoding, 'UTF-8//IGNORE', $contents);
            if (is_string($converted) && $converted !== '') {
                return $converted;
            }
        }

        return null;
    }

    private function isValidUtf8(string $value): bool
    {
        if (function_exists('mb_check_encoding')) {
            return mb_check_encoding($value, 'UTF-8');
        }

        return preg_match('//u', $value) === 1;
    }

    private function normalizeDate(string $raw): ?string
    {
        if (!preg_match('/(\d{8})/', $raw, $matches)) {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('!Ymd', $matches[1]);
        if (!$date) {
            return null;
        }

        return $date->format('Y-m-d');
    }
}
