<?php

require __DIR__ . '/../bootstrap.php';

use Application\Models\Plano;

$plano = Plano::find(1);

if ($plano) {
    echo "ID: {$plano->id}\n";
    echo "Code: " . ($plano->code ?? 'NULL') . "\n";
    echo "Nome: " . ($plano->nome ?? 'NULL') . "\n";
} else {
    echo "Plano ID 1 não encontrado\n";
}

echo "\n--- Todos os planos ---\n";
$planos = Plano::all();
foreach ($planos as $p) {
    echo "ID: {$p->id} | Code: " . ($p->code ?? 'NULL') . " | Nome: " . ($p->nome ?? 'NULL') . "\n";
}
