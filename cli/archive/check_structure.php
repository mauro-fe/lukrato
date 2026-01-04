<?php
require_once __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

echo "\n=== ESTRUTURA DA TABELA LANCAMENTOS ===\n\n";

try {
    $columns = DB::select('DESCRIBE lancamentos');

    foreach ($columns as $col) {
        $key = $col->Key ? " [Key: {$col->Key}]" : "";
        $null = $col->Null === 'YES' ? ' NULL' : ' NOT NULL';
        echo "- {$col->Field}: {$col->Type}{$null}{$key}\n";
    }

    echo "\n=== ESTRUTURA DA TABELA PARCELAMENTOS ===\n\n";

    $columns = DB::select('DESCRIBE parcelamentos');

    foreach ($columns as $col) {
        $key = $col->Key ? " [Key: {$col->Key}]" : "";
        $null = $col->Null === 'YES' ? ' NULL' : ' NOT NULL';
        echo "- {$col->Field}: {$col->Type}{$null}{$key}\n";
    }
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}

echo "\n";
