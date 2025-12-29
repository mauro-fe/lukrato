<?php

require_once __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

echo "=== COLUNAS DA TABELA parcelamentos ===\n\n";

$columns = DB::select('SHOW COLUMNS FROM parcelamentos');

foreach ($columns as $col) {
    echo "- {$col->Field} ({$col->Type})\n";
}

echo "\n✅ Verificação concluída!\n";
