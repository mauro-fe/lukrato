<?php

require_once __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

echo "Buscando tabelas de usuários...\n";

$tables = DB::select('SHOW TABLES');

foreach ($tables as $table) {
    foreach ($table as $value) {
        if (stripos($value, 'user') !== false || stripos($value, 'usuario') !== false) {
            echo "- $value\n";
        }
    }
}
