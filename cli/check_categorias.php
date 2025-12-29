<?php
require __DIR__ . '/../bootstrap.php';

use Application\Models\Categoria;

$userId = 22;

echo "=== Todas as categorias do usuário {$userId} ===\n\n";

$categorias = Categoria::where('user_id', $userId)->get();

echo "Total: " . $categorias->count() . "\n\n";

foreach ($categorias as $cat) {
    echo "ID: {$cat->id} | Nome: '{$cat->nome}' | Tipo: {$cat->tipo}\n";
}

echo "\n=== Buscando 'Mercado' especificamente ===\n\n";

$mercado = Categoria::where('user_id', $userId)
    ->whereRaw('LOWER(nome) = ?', ['mercado'])
    ->get();

echo "Encontrados: " . $mercado->count() . "\n";
foreach ($mercado as $m) {
    echo "ID: {$m->id} - Nome: '{$m->nome}' - Tipo: {$m->tipo}\n";
}
