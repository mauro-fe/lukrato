<?php

require_once __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

echo "=== DEBUG: Tabela parcelamentos ===\n\n";

$parcelamentos = DB::table('parcelamentos')
    ->where('user_id', 1)
    ->orderBy('id', 'desc')
    ->get();

echo "Total de registros: " . count($parcelamentos) . "\n\n";

foreach ($parcelamentos as $p) {
    echo "ID: {$p->id}\n";
    echo "Descrição: {$p->descricao}\n";
    echo "data_criacao: {$p->data_criacao}\n";
    echo "created_at: {$p->created_at}\n";
    echo "cartao_credito_id: {$p->cartao_credito_id}\n";
    echo "\n";
}
