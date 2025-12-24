<?php
require __DIR__ . '/../bootstrap.php';

use Application\Models\Categoria;

$userId = 23;

$categorias = Categoria::where('user_id', $userId)->get();

echo "Total de categorias do usuário {$userId}: " . $categorias->count() . PHP_EOL;

foreach ($categorias as $cat) {
    echo "- ID: {$cat->id} | Nome: {$cat->nome} | Tipo: {$cat->tipo}" . PHP_EOL;
}
