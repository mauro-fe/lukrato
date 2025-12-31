<?php

require dirname(__DIR__) . '/bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

try {
    $columns = DB::select("SHOW COLUMNS FROM lancamentos");
    
    echo "Estrutura da tabela lancamentos:\n";
    echo str_repeat('=', 60) . "\n";
    
    foreach ($columns as $column) {
        printf("%-25s %-20s %-8s\n", $column->Field, $column->Type, $column->Null);
    }
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
