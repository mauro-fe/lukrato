<?php

require_once __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

echo "=== VERIFICANDO LIMITE DOS CARTÕES NO BANCO ===\n\n";

$cartoes = DB::table('cartoes_credito')
    ->orderBy('id', 'desc')
    ->limit(5)
    ->get();

foreach ($cartoes as $cartao) {
    echo "ID: {$cartao->id}\n";
    echo "Nome: {$cartao->nome_cartao}\n";
    echo "Limite Total: {$cartao->limite_total}\n";
    echo "Limite Disponível: {$cartao->limite_disponivel}\n";
    echo "Data Criação: {$cartao->created_at}\n";
    echo "------------------------------------------------------------\n";
}

echo "\nTotal de cartões: " . count($cartoes) . "\n";
