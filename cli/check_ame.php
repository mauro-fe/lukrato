<?php

require __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

$contas = DB::table('contas')
    ->where('user_id', 23)
    ->where('nome', 'Santander')
    ->orderBy('id')
    ->get();

echo "Total de contas Santander: " . $contas->count() . "\n\n";

foreach ($contas as $conta) {
    echo "ID: {$conta->id}\n";
    echo "Nome: {$conta->nome}\n";
    echo "Instituição ID: {$conta->instituicao_financeira_id}\n";
    echo "Criada em: {$conta->created_at}\n";
    echo str_repeat('-', 50) . "\n";
}
