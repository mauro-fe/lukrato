<?php
require_once __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

$cols = DB::select("SHOW COLUMNS FROM lancamentos LIKE 'data_competencia'");

if (count($cols) > 0) {
    echo "✅ Coluna data_competencia EXISTE na tabela lancamentos\n";
} else {
    echo "❌ Coluna data_competencia NÃO existe - executando migration...\n";

    // Executar migration manualmente
    require_once __DIR__ . '/../database/migrations/2026_01_29_000001_add_competencia_fields_to_lancamentos.php';
}

// Listar todas as colunas
echo "\nColunas da tabela lancamentos:\n";
$allCols = DB::select("SHOW COLUMNS FROM lancamentos");
foreach ($allCols as $col) {
    echo "  - {$col->Field} ({$col->Type})\n";
}
