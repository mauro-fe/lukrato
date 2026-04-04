<?php

declare(strict_types=1);

namespace Application\Services\Importacao\Parsers;

use Application\DTO\Importacao\ImportProfileConfigDTO;
use Application\DTO\Importacao\NormalizedImportRowDTO;
use Application\Services\Importacao\Contracts\ImportParserInterface;
use Application\Services\Importacao\ImportSanitizer;
use Application\Services\Importacao\ImportSecurityPolicy;

class CsvImportParser implements ImportParserInterface
{
    public function supports(string $sourceType): bool
    {
        return strtolower(trim($sourceType)) === 'csv';
    }

    public function parse(string $contents, ImportProfileConfigDTO $profile): array
    {
        $contents = ltrim(trim($contents), "\xEF\xBB\xBF");
        if ($contents === '') {
            return [];
        }

        $options = $this->resolveOptions($profile);
        $rows = $this->readCsvRows($contents, $options['delimiter'], $this->maxReadableLines((int) $options['start_row']));
        if ($rows === []) {
            return [];
        }

        return $options['mapping_mode'] === 'manual'
            ? $this->parseManual($rows, $options)
            : $this->parseAuto($rows, $options);
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveOptions(ImportProfileConfigDTO $profile): array
    {
        $options = is_array($profile->options ?? null) ? $profile->options : [];
        $hasHeader = $this->normalizeBoolean($options['csv_has_header'] ?? true);

        return [
            'mapping_mode' => $this->normalizeMappingMode($options['csv_mapping_mode'] ?? 'auto'),
            'start_row' => $this->normalizeStartRow($options['csv_start_row'] ?? ($hasHeader ? 2 : 1), $hasHeader),
            'has_header' => $hasHeader,
            'delimiter' => $this->normalizeDelimiter($options['csv_delimiter'] ?? ';'),
            'date_format' => $this->normalizeDateFormat($options['csv_date_format'] ?? 'd/m/Y'),
            'decimal_separator' => $this->normalizeDecimalSeparator($options['csv_decimal_separator'] ?? ','),
            'column_map' => is_array($options['csv_column_map'] ?? null) ? $options['csv_column_map'] : [],
        ];
    }

    /**
     * @return array<int, array{row:int, values:array<int, string>}>
     */
    private function readCsvRows(string $contents, string $delimiter, int $maxReadableLines): array
    {
        $handle = fopen('php://temp', 'r+');
        if (!is_resource($handle)) {
            throw new \RuntimeException('Não foi possível carregar CSV para importação.');
        }

        fwrite($handle, $contents);
        rewind($handle);

        $rows = [];
        $lineNumber = 0;
        while (($line = fgetcsv($handle, 0, $delimiter)) !== false) {
            $lineNumber++;
            if ($lineNumber > $maxReadableLines) {
                fclose($handle);
                throw new \InvalidArgumentException(ImportSecurityPolicy::rowsLimitMessage());
            }

            if (!is_array($line)) {
                continue;
            }

            $rows[] = [
                'row' => $lineNumber,
                'values' => array_map(
                    static fn(mixed $value): string => trim((string) $value),
                    $line
                ),
            ];
        }

        fclose($handle);

        return $rows;
    }

    /**
     * @param array<int, array{row:int, values:array<int, string>}> $rows
     * @param array<string, mixed> $options
     * @return array<int, NormalizedImportRowDTO>
     */
    private function parseAuto(array $rows, array $options): array
    {
        $maxRowsPerFile = ImportSecurityPolicy::maxRowsPerFile();

        if (!$options['has_header']) {
            throw new \InvalidArgumentException('CSV automático exige cabeçalho. Ative cabeçalho ou use mapeamento manual.');
        }

        $headerRowNumber = max(1, ((int) $options['start_row']) - 1);
        $headerValues = $this->findRowValues($rows, $headerRowNumber);
        if ($headerValues === null || $this->isLineEmpty($headerValues)) {
            throw new \InvalidArgumentException('Cabeçalho CSV não encontrado para o modo automático.');
        }

        $map = $this->buildHeaderColumnMap($headerValues);
        $missingRequired = $this->missingRequiredMappings($map);
        if ($missingRequired !== []) {
            throw new \InvalidArgumentException(
                'Cabeçalho CSV sem campos obrigatórios: ' . implode(', ', $missingRequired)
            );
        }

        $rowsDto = [];
        foreach ($rows as $item) {
            $lineNumber = (int) ($item['row'] ?? 0);
            $line = is_array($item['values'] ?? null) ? $item['values'] : [];
            if ($lineNumber < (int) $options['start_row'] || $this->isLineEmpty($line)) {
                continue;
            }

            $dto = $this->lineToRow($line, $map, $lineNumber, $options);
            if ($dto instanceof NormalizedImportRowDTO) {
                $rowsDto[] = $dto;
                if (count($rowsDto) > $maxRowsPerFile) {
                    throw new \InvalidArgumentException(ImportSecurityPolicy::rowsLimitMessage($maxRowsPerFile));
                }
            }
        }

        return $rowsDto;
    }

    /**
     * @param array<int, array{row:int, values:array<int, string>}> $rows
     * @param array<string, mixed> $options
     * @return array<int, NormalizedImportRowDTO>
     */
    private function parseManual(array $rows, array $options): array
    {
        $maxRowsPerFile = ImportSecurityPolicy::maxRowsPerFile();
        $map = $this->buildManualColumnMap(is_array($options['column_map'] ?? null) ? $options['column_map'] : []);
        $missingRequired = $this->missingRequiredMappings($map);
        if ($missingRequired !== []) {
            throw new \InvalidArgumentException(
                'Mapeamento CSV manual incompleto. Campos obrigatórios: ' . implode(', ', $missingRequired)
            );
        }

        $rowsDto = [];
        foreach ($rows as $item) {
            $lineNumber = (int) ($item['row'] ?? 0);
            $line = is_array($item['values'] ?? null) ? $item['values'] : [];
            if ($lineNumber < (int) $options['start_row'] || $this->isLineEmpty($line)) {
                continue;
            }

            $dto = $this->lineToRow($line, $map, $lineNumber, $options);
            if ($dto instanceof NormalizedImportRowDTO) {
                $rowsDto[] = $dto;
                if (count($rowsDto) > $maxRowsPerFile) {
                    throw new \InvalidArgumentException(ImportSecurityPolicy::rowsLimitMessage($maxRowsPerFile));
                }
            }
        }

        return $rowsDto;
    }

    /**
     * @param array<int, string> $line
     * @param array<string, int|null> $map
     * @param array<string, mixed> $options
     */
    private function lineToRow(array $line, array $map, int $lineNumber, array $options): ?NormalizedImportRowDTO
    {
        $rawDate = $this->pickValue($line, $map['data'] ?? null);
        $rawDescription = $this->pickValue($line, $map['descricao'] ?? null);
        $rawAmount = $this->pickValue($line, $map['valor'] ?? null);
        $rawType = $this->pickValue($line, $map['tipo'] ?? null);

        $date = $this->normalizeDate($rawDate, (string) $options['date_format']);
        $amount = $this->normalizeAmount($rawAmount, (string) $options['decimal_separator']);
        $description = ImportSanitizer::sanitizeText($rawDescription, 190);
        $type = $this->normalizeType($rawType, $amount);

        if ($date === null || $amount === null || $description === '' || $type === null || abs($amount) <= 0.0) {
            return null;
        }

        $categoria = $this->pickValue($line, $map['categoria'] ?? null);
        $subcategoria = $this->pickValue($line, $map['subcategoria'] ?? null);
        $observacao = ImportSanitizer::sanitizeText($this->pickValue($line, $map['observacao'] ?? null), 500, true);
        $idExterno = ImportSanitizer::sanitizeText($this->pickValue($line, $map['id_externo'] ?? null), 120);

        return NormalizedImportRowDTO::fromArray([
            'date' => $date,
            'amount' => abs($amount),
            'type' => $type,
            'description' => $description,
            'memo' => $observacao !== '' ? $observacao : null,
            'external_id' => $idExterno !== '' ? $idExterno : null,
            'raw' => [
                'line_number' => $lineNumber,
                'line' => $line,
                'categoria' => $categoria,
                'subcategoria' => $subcategoria,
                'observacao' => $observacao,
            ],
        ]);
    }

    /**
     * @param array<int, string> $header
     * @return array<string, int|null>
     */
    private function buildHeaderColumnMap(array $header): array
    {
        $aliases = [
            'tipo' => ['tipo', 'type', 'natureza'],
            'data' => ['data', 'date', 'dt', 'posted_on', 'dt_posted'],
            'descricao' => ['descricao', 'description', 'historico', 'memo', 'name', 'detalhe'],
            'valor' => ['valor', 'amount', 'value', 'trnamt'],
            'categoria' => ['categoria', 'category'],
            'subcategoria' => ['subcategoria', 'subcategory', 'sub_category'],
            'observacao' => ['observacao', 'obs', 'memo'],
            'id_externo' => ['id_externo', 'external_id', 'fitid', 'id'],
        ];

        $normalizedHeader = [];
        foreach ($header as $index => $column) {
            $normalizedHeader[$index] = $this->normalizeHeader((string) $column);
        }

        $map = [];
        foreach ($aliases as $target => $names) {
            $map[$target] = null;
            foreach ($names as $name) {
                $position = array_search($name, $normalizedHeader, true);
                if ($position !== false) {
                    $map[$target] = (int) $position;
                    break;
                }
            }
        }

        return $map;
    }

    /**
     * @param array<string, mixed> $columnMap
     * @return array<string, int|null>
     */
    private function buildManualColumnMap(array $columnMap): array
    {
        return [
            'tipo' => $this->columnReferenceToIndex((string) ($columnMap['tipo'] ?? '')),
            'data' => $this->columnReferenceToIndex((string) ($columnMap['data'] ?? '')),
            'descricao' => $this->columnReferenceToIndex((string) ($columnMap['descricao'] ?? '')),
            'valor' => $this->columnReferenceToIndex((string) ($columnMap['valor'] ?? '')),
            'categoria' => $this->columnReferenceToIndex((string) ($columnMap['categoria'] ?? '')),
            'subcategoria' => $this->columnReferenceToIndex((string) ($columnMap['subcategoria'] ?? '')),
            'observacao' => $this->columnReferenceToIndex((string) ($columnMap['observacao'] ?? '')),
            'id_externo' => $this->columnReferenceToIndex((string) ($columnMap['id_externo'] ?? '')),
        ];
    }

    /**
     * @param array<string, int|null> $map
     * @return array<int, string>
     */
    private function missingRequiredMappings(array $map): array
    {
        $required = ['tipo', 'data', 'descricao', 'valor'];
        $missing = [];

        foreach ($required as $field) {
            if (!isset($map[$field]) || $map[$field] === null || (int) $map[$field] < 0) {
                $missing[] = $field;
            }
        }

        return $missing;
    }

    /**
     * @param array<int, array{row:int, values:array<int, string>}> $rows
     * @return array<int, string>|null
     */
    private function findRowValues(array $rows, int $rowNumber): ?array
    {
        foreach ($rows as $item) {
            if ((int) ($item['row'] ?? 0) === $rowNumber) {
                return is_array($item['values'] ?? null) ? $item['values'] : null;
            }
        }

        return null;
    }

    /**
     * @param array<int, string> $line
     */
    private function pickValue(array $line, ?int $position): string
    {
        if ($position === null || $position < 0) {
            return '';
        }

        return trim((string) ($line[$position] ?? ''));
    }

    private function normalizeType(string $rawType, ?float $amount): ?string
    {
        $normalized = strtolower(trim($rawType));
        if ($normalized === '') {
            return $amount !== null && $amount >= 0 ? 'receita' : 'despesa';
        }

        if (in_array($normalized, ['despesa', 'debit', 'debito', 'out', 'saida'], true)) {
            return 'despesa';
        }

        if (in_array($normalized, ['receita', 'credit', 'credito', 'in', 'entrada'], true)) {
            return 'receita';
        }

        return null;
    }

    private function normalizeAmount(string $raw, string $decimalSeparator): ?float
    {
        if ($raw === '') {
            return null;
        }

        $normalized = str_replace(['R$', ' '], '', trim($raw));
        if ($decimalSeparator === '.') {
            $normalized = str_replace(',', '', $normalized);
        } else {
            $normalized = str_replace('.', '', $normalized);
            $normalized = str_replace(',', '.', $normalized);
        }

        $normalized = preg_replace('/[^0-9\.\-]/', '', $normalized) ?? '';
        if ($normalized === '' || !is_numeric($normalized)) {
            return null;
        }

        return round((float) $normalized, 2);
    }

    private function normalizeDate(string $raw, string $preferredFormat): ?string
    {
        if ($raw === '') {
            return null;
        }

        $raw = trim($raw);
        $candidates = array_values(array_unique(array_filter([
            trim($preferredFormat),
            'Y-m-d',
            'd/m/Y',
            'd-m-Y',
            'm/d/Y',
            'd.m.Y',
            'Ymd',
        ])));

        foreach ($candidates as $format) {
            $date = \DateTimeImmutable::createFromFormat('!' . $format, $raw);
            if ($date instanceof \DateTimeImmutable) {
                return $date->format('Y-m-d');
            }
        }

        return null;
    }

    private function normalizeHeader(string $value): string
    {
        $value = strtolower(trim($value));
        $transliterated = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if ($transliterated !== false) {
            $value = strtolower($transliterated);
        }

        $value = preg_replace('/[^a-z0-9_]+/', '_', $value) ?? '';

        return trim($value, '_');
    }

    private function columnReferenceToIndex(string $reference): ?int
    {
        $reference = strtoupper(trim($reference));
        if ($reference === '') {
            return null;
        }

        if (preg_match('/^\d+$/', $reference) === 1) {
            $position = (int) $reference;
            return $position > 0 ? $position - 1 : null;
        }

        if (preg_match('/^[A-Z]+$/', $reference) !== 1) {
            return null;
        }

        $index = 0;
        $length = strlen($reference);
        for ($i = 0; $i < $length; $i++) {
            $index = ($index * 26) + (ord($reference[$i]) - 64);
        }

        return $index > 0 ? $index - 1 : null;
    }

    /**
     * @param array<int, string> $line
     */
    private function isLineEmpty(array $line): bool
    {
        foreach ($line as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function maxReadableLines(int $startRow): int
    {
        return ImportSecurityPolicy::maxRowsPerFile() + max(2, $startRow);
    }

    private function normalizeMappingMode(mixed $value): string
    {
        $mode = strtolower(trim((string) $value));

        return in_array($mode, ['auto', 'manual'], true) ? $mode : 'auto';
    }

    private function normalizeStartRow(mixed $value, bool $hasHeader): int
    {
        $row = (int) $value;
        if ($row > 0) {
            return $row;
        }

        return $hasHeader ? 2 : 1;
    }

    private function normalizeDelimiter(mixed $value): string
    {
        $delimiter = trim((string) $value);
        if ($delimiter === '') {
            return ';';
        }

        $lower = strtolower($delimiter);
        if ($lower === 'tab' || $lower === '\\t') {
            return "\t";
        }

        if (mb_strlen($delimiter) !== 1) {
            return ';';
        }

        return $delimiter;
    }

    private function normalizeDateFormat(mixed $value): string
    {
        $format = trim((string) $value);

        return $format !== '' ? mb_substr($format, 0, 20) : 'd/m/Y';
    }

    private function normalizeDecimalSeparator(mixed $value): string
    {
        $separator = trim((string) $value);

        return $separator === '.' ? '.' : ',';
    }

    private function normalizeBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $normalized = strtolower(trim((string) $value));

        return in_array($normalized, ['1', 'true', 'sim', 'yes', 'on'], true);
    }
}
