<?php

require dirname(__DIR__) . '/bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

// Verificar estrutura da tabela contas
$columns = DB::select("SHOW COLUMNS FROM contas");

echo "Estrutura da tabela contas:\n";
foreach ($columns as $column) {
    echo "- {$column->Field}: {$column->Type} (Key: {$column->Key})\n";
}
