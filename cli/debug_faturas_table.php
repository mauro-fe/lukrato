<?php
require_once __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

$columns = DB::select("SHOW COLUMNS FROM achievements");
echo "Colunas da tabela achievements:\n";
foreach ($columns as $col) {
    echo "  - {$col->Field} ({$col->Type})\n";
}
