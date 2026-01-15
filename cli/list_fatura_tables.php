<?php

require_once __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

try {
    $tables = DB::select('SHOW TABLES');

    echo "Tabelas relacionadas a faturas:\n";
    echo "================================\n";

    foreach ($tables as $table) {
        foreach ($table as $value) {
            if (stripos($value, 'fatura') !== false) {
                echo "- $value\n";
            }
        }
    }
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
