<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';
require BASE_PATH . '/config/config.php';

use Illuminate\Database\Capsule\Manager as DB;

$outputDir = BASE_PATH . '/storage/reports';
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0777, true);
}

$timestamp = date('Ymd_His');
$jsonFile = $outputDir . "/column_usage_report_{$timestamp}.json";
$csvFile = $outputDir . "/column_usage_report_{$timestamp}.csv";

$tables = DB::select("
    SELECT TABLE_NAME
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_TYPE = 'BASE TABLE'
    ORDER BY TABLE_NAME
");

$tables = array_map(static fn($row) => $row->TABLE_NAME, $tables);

$columns = DB::select("
    SELECT
        c.TABLE_NAME,
        c.COLUMN_NAME,
        c.DATA_TYPE,
        c.IS_NULLABLE,
        c.COLUMN_DEFAULT,
        c.COLUMN_KEY,
        c.EXTRA
    FROM information_schema.COLUMNS c
    WHERE c.TABLE_SCHEMA = DATABASE()
    ORDER BY c.TABLE_NAME, c.ORDINAL_POSITION
");

$tableRows = [];
foreach ($tables as $table) {
    $row = DB::selectOne("
        SELECT TABLE_ROWS
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
    ", [$table]);

    $tableRows[$table] = (int) ($row->TABLE_ROWS ?? 0);
}

$searchRoots = [
    BASE_PATH . '/Application',
    BASE_PATH . '/routes',
    BASE_PATH . '/cli',
    BASE_PATH . '/views',
    BASE_PATH . '/config',
];

$skipCodeSearchColumns = [
    'id',
    'created_at',
    'updated_at',
    'deleted_at',
    'user_id',
    'status',
    'type',
    'name',
    'code',
    'email',
    'token',
    'selector',
    'token_hash',
];

$report = [];

foreach ($columns as $column) {
    $table = $column->TABLE_NAME;
    $name = $column->COLUMN_NAME;

    $quotedTable = '`' . str_replace('`', '``', $table) . '`';
    $quotedColumn = '`' . str_replace('`', '``', $name) . '`';

    $stats = DB::selectOne("
        SELECT
            COUNT(*) AS total_rows,
            SUM(CASE WHEN {$quotedColumn} IS NOT NULL THEN 1 ELSE 0 END) AS non_null_rows,
            COUNT(DISTINCT {$quotedColumn}) AS distinct_non_null_values
        FROM {$quotedTable}
    ");

    $sampleValues = [];
    if ((int) $stats->non_null_rows > 0 && (int) $stats->distinct_non_null_values <= 5) {
        $sampleQuery = DB::select("
            SELECT {$quotedColumn} AS value, COUNT(*) AS qty
            FROM {$quotedTable}
            WHERE {$quotedColumn} IS NOT NULL
            GROUP BY {$quotedColumn}
            ORDER BY qty DESC
            LIMIT 5
        ");

        foreach ($sampleQuery as $sample) {
            $value = $sample->value;
            if (is_string($value) && mb_strlen($value) > 80) {
                $value = mb_substr($value, 0, 77) . '...';
            }

            $sampleValues[] = [
                'value' => $value,
                'qty' => (int) $sample->qty,
            ];
        }
    }

    $codeHits = null;
    if (!in_array($name, $skipCodeSearchColumns, true)) {
        $pattern = '\b' . preg_quote($name, '/') . '\b';
        $parts = [];
        foreach ($searchRoots as $root) {
            $parts[] = '"' . $root . '"';
        }

        $command = 'rg -n --pcre2 "' . $pattern . '" ' . implode(' ', $parts) . ' -g "*.php"';
        $output = [];
        $exitCode = 0;
        exec($command, $output, $exitCode);
        $codeHits = $exitCode === 0 ? count($output) : 0;
    }

    $report[] = [
        'table' => $table,
        'column' => $name,
        'data_type' => $column->DATA_TYPE,
        'is_nullable' => $column->IS_NULLABLE,
        'column_default' => $column->COLUMN_DEFAULT,
        'column_key' => $column->COLUMN_KEY,
        'extra' => $column->EXTRA,
        'estimated_table_rows' => $tableRows[$table] ?? null,
        'total_rows' => (int) $stats->total_rows,
        'non_null_rows' => (int) $stats->non_null_rows,
        'distinct_non_null_values' => (int) $stats->distinct_non_null_values,
        'sample_values' => $sampleValues,
        'code_hits' => $codeHits,
    ];
}

file_put_contents(
    $jsonFile,
    json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
);

$csv = fopen($csvFile, 'wb');
fputcsv($csv, [
    'table',
    'column',
    'data_type',
    'is_nullable',
    'column_default',
    'column_key',
    'extra',
    'estimated_table_rows',
    'total_rows',
    'non_null_rows',
    'distinct_non_null_values',
    'code_hits',
    'sample_values_json',
]);

foreach ($report as $row) {
    fputcsv($csv, [
        $row['table'],
        $row['column'],
        $row['data_type'],
        $row['is_nullable'],
        $row['column_default'],
        $row['column_key'],
        $row['extra'],
        $row['estimated_table_rows'],
        $row['total_rows'],
        $row['non_null_rows'],
        $row['distinct_non_null_values'],
        $row['code_hits'],
        json_encode($row['sample_values'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    ]);
}

fclose($csv);

$candidateColumns = array_values(array_filter($report, static function (array $row): bool {
    if ($row['column_key'] === 'PRI') {
        return false;
    }

    if ($row['total_rows'] === 0) {
        return false;
    }

    if ($row['non_null_rows'] === 0) {
        return true;
    }

    if ($row['distinct_non_null_values'] === 1 && ($row['code_hits'] === 0 || $row['code_hits'] === null)) {
        return true;
    }

    return false;
}));

echo json_encode([
    'database' => DB_NAME,
    'generated_at' => date(DATE_ATOM),
    'json_file' => $jsonFile,
    'csv_file' => $csvFile,
    'total_columns' => count($report),
    'candidate_count' => count($candidateColumns),
    'top_candidates' => array_slice($candidateColumns, 0, 30),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
