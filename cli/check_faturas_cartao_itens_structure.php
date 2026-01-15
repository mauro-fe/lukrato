<?php

require_once __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

echo "Estrutura da tabela faturas_cartao_itens:\n";
echo "==========================================\n\n";

$columns = DB::select('DESCRIBE faturas_cartao_itens');

foreach ($columns as $col) {
    echo "- {$col->Field} ({$col->Type}) - {$col->Null} - {$col->Key} - {$col->Default}\n";
}
