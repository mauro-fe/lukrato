<?php

declare(strict_types=1);

namespace Application\Services\Importacao;

use Application\DTO\Importacao\ImportProfileConfigDTO;
use Application\Models\ImportacaoPerfil;

class ImportProfileConfigService
{
    public function createDefaultProfile(int $contaId, string $sourceType = 'ofx'): ImportProfileConfigDTO
    {
        return ImportProfileConfigDTO::fromArray([
            'conta_id' => $contaId,
            'source_type' => $sourceType,
            'label' => 'Perfil base',
            'agencia' => null,
            'numero_conta' => null,
            'options' => $this->defaultCsvOptions(),
        ]);
    }

    public function getForUserAndConta(int $userId, int $contaId, string $fallbackSourceType = 'ofx'): ImportProfileConfigDTO
    {
        $row = ImportacaoPerfil::query()
            ->where('user_id', $userId)
            ->where('conta_id', $contaId)
            ->first();

        if (!$row) {
            return $this->createDefaultProfile($contaId, $fallbackSourceType);
        }

        return ImportProfileConfigDTO::fromArray([
            'conta_id' => $contaId,
            'source_type' => $row->source_type ?: $fallbackSourceType,
            'label' => $row->label,
            'agencia' => $row->agencia,
            'numero_conta' => $row->numero_conta,
            'options' => $this->normalizeOptions($this->decodeOptions((string) ($row->options_json ?? ''))),
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function saveForUserAndConta(int $userId, int $contaId, array $payload): ImportProfileConfigDTO
    {
        $sourceType = $this->normalizeSourceType($payload['source_type'] ?? 'ofx');
        $label = $this->normalizeOptionalString($payload['label'] ?? 'Perfil base');
        $agencia = $this->normalizeOptionalString($payload['agencia'] ?? null);
        $numeroConta = $this->normalizeOptionalString($payload['numero_conta'] ?? null);
        $options = $this->normalizeOptions(is_array($payload['options'] ?? null) ? $payload['options'] : []);

        ImportacaoPerfil::query()->updateOrCreate(
            [
                'user_id' => $userId,
                'conta_id' => $contaId,
            ],
            [
                'source_type' => $sourceType,
                'label' => $label,
                'agencia' => $agencia,
                'numero_conta' => $numeroConta,
                'options_json' => json_encode($options, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),
            ]
        );

        return ImportProfileConfigDTO::fromArray([
            'conta_id' => $contaId,
            'source_type' => $sourceType,
            'label' => $label,
            'agencia' => $agencia,
            'numero_conta' => $numeroConta,
            'options' => $options,
        ]);
    }

    private function normalizeSourceType(mixed $value): string
    {
        $normalized = strtolower(trim((string) $value));

        return in_array($normalized, ['ofx', 'csv'], true) ? $normalized : 'ofx';
    }

    private function normalizeOptionalString(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized !== '' ? $normalized : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeOptions(string $json): array
    {
        if ($json === '') {
            return [];
        }

        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    private function normalizeOptions(array $options): array
    {
        $defaults = $this->defaultCsvOptions();

        $csvMappingMode = $this->normalizeCsvMappingMode($options['csv_mapping_mode'] ?? $defaults['csv_mapping_mode']);
        $csvHasHeader = $this->normalizeBoolean($options['csv_has_header'] ?? $defaults['csv_has_header']);
        $csvDelimiter = $this->normalizeCsvDelimiter($options['csv_delimiter'] ?? $defaults['csv_delimiter']);
        $csvDateFormat = $this->normalizeCsvDateFormat($options['csv_date_format'] ?? $defaults['csv_date_format']);
        $csvDecimalSeparator = $this->normalizeCsvDecimalSeparator($options['csv_decimal_separator'] ?? $defaults['csv_decimal_separator']);
        $csvStartRow = $this->normalizeCsvStartRow($options['csv_start_row'] ?? ($csvHasHeader ? 2 : 1), $csvHasHeader);

        $rawColumnMap = is_array($options['csv_column_map'] ?? null) ? $options['csv_column_map'] : [];
        foreach (['tipo', 'data', 'descricao', 'valor', 'categoria', 'subcategoria', 'observacao', 'id_externo'] as $field) {
            $legacyKey = 'csv_column_' . $field;
            if (array_key_exists($legacyKey, $options) && !array_key_exists($field, $rawColumnMap)) {
                $rawColumnMap[$field] = $options[$legacyKey];
            }
        }
        $csvColumnMap = $this->normalizeCsvColumnMap($rawColumnMap);

        return [
            'csv_mapping_mode' => $csvMappingMode,
            'csv_start_row' => $csvStartRow,
            'csv_has_header' => $csvHasHeader,
            'csv_delimiter' => $csvDelimiter,
            'csv_date_format' => $csvDateFormat,
            'csv_decimal_separator' => $csvDecimalSeparator,
            'csv_column_map' => $csvColumnMap,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultCsvOptions(): array
    {
        return [
            'csv_mapping_mode' => 'auto',
            'csv_start_row' => 2,
            'csv_has_header' => true,
            'csv_delimiter' => ';',
            'csv_date_format' => 'd/m/Y',
            'csv_decimal_separator' => ',',
            'csv_column_map' => [
                'tipo' => 'A',
                'data' => 'B',
                'descricao' => 'C',
                'valor' => 'D',
                'categoria' => 'E',
                'subcategoria' => 'F',
                'observacao' => 'G',
                'id_externo' => 'H',
            ],
        ];
    }

    private function normalizeCsvMappingMode(mixed $value): string
    {
        $mode = strtolower(trim((string) $value));

        return in_array($mode, ['auto', 'manual'], true) ? $mode : 'auto';
    }

    private function normalizeCsvStartRow(mixed $value, bool $hasHeader): int
    {
        $default = $hasHeader ? 2 : 1;
        $row = (int) $value;

        return $row > 0 ? $row : $default;
    }

    private function normalizeCsvDelimiter(mixed $value): string
    {
        $delimiter = trim((string) $value);
        if ($delimiter === '') {
            return ';';
        }

        $lower = strtolower($delimiter);
        if ($lower === '\\t' || $lower === 'tab') {
            return "\t";
        }

        if (mb_strlen($delimiter) === 1) {
            return $delimiter;
        }

        return ';';
    }

    private function normalizeCsvDateFormat(mixed $value): string
    {
        $format = trim((string) $value);

        return $format !== '' ? mb_substr($format, 0, 20) : 'd/m/Y';
    }

    private function normalizeCsvDecimalSeparator(mixed $value): string
    {
        $separator = trim((string) $value);

        return $separator === '.' ? '.' : ',';
    }

    /**
     * @param array<string, mixed> $columnMap
     * @return array<string, string>
     */
    private function normalizeCsvColumnMap(array $columnMap): array
    {
        $normalized = [
            'tipo' => '',
            'data' => '',
            'descricao' => '',
            'valor' => '',
            'categoria' => '',
            'subcategoria' => '',
            'observacao' => '',
            'id_externo' => '',
        ];

        $aliases = [
            'type' => 'tipo',
            'tipo' => 'tipo',
            'date' => 'data',
            'data' => 'data',
            'description' => 'descricao',
            'descricao' => 'descricao',
            'amount' => 'valor',
            'valor' => 'valor',
            'category' => 'categoria',
            'categoria' => 'categoria',
            'subcategory' => 'subcategoria',
            'subcategoria' => 'subcategoria',
            'memo' => 'observacao',
            'observacao' => 'observacao',
            'external_id' => 'id_externo',
            'id_externo' => 'id_externo',
        ];

        foreach ($aliases as $key => $target) {
            if (!array_key_exists($key, $columnMap)) {
                continue;
            }

            $raw = strtoupper(trim((string) $columnMap[$key]));
            if ($raw === '') {
                $normalized[$target] = '';
                continue;
            }

            if (preg_match('/^\d+$/', $raw) || preg_match('/^[A-Z]+$/', $raw)) {
                $normalized[$target] = mb_substr($raw, 0, 8);
            }
        }

        return $normalized;
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
