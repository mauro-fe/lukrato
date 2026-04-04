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
        $trimmed = trim($contents);
        if ($trimmed === '') {
            return [];
        }

        $normalized = strtoupper($trimmed);
        if (!str_contains($normalized, '<OFX') && !str_contains($normalized, '<STMTTRN>')) {
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

            return array_values($matches[1]);
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
        $normalized = str_replace(',', '.', trim($raw));
        $normalized = preg_replace('/[^0-9\.\-]/', '', $normalized) ?? '';

        if ($normalized === '' || !is_numeric($normalized)) {
            return null;
        }

        return round((float) $normalized, 2);
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
