#!/usr/bin/env php
<?php
/**
 * Debug faturas e cartões
 */

require_once dirname(__DIR__) . '/bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

$userId = $argv[1] ?? 1;

echo "=== Cartões do usuário {$userId} ===" . PHP_EOL;
$cartoes = DB::table('cartoes_credito')->where('user_id', $userId)->get();
foreach ($cartoes as $c) {
    $arquivado = $c->arquivado ? ' [ARQUIVADO]' : '';
    echo "  ID: {$c->id} - {$c->nome_cartao}{$arquivado}" . PHP_EOL;
}

echo PHP_EOL . "=== Faturas (tabela 'faturas') do usuário {$userId} ===" . PHP_EOL;
$faturas = DB::table('faturas')->where('user_id', $userId)->get();
if ($faturas->isEmpty()) {
    echo "  Nenhuma fatura na tabela 'faturas'" . PHP_EOL;
} else {
    foreach ($faturas as $f) {
        echo "  ID: {$f->id} - Cartão ID: {$f->cartao_credito_id} - {$f->descricao} - Status: {$f->status}" . PHP_EOL;
    }
}

echo PHP_EOL . "=== Itens de Fatura (tabela 'faturas_cartao_itens') do usuário {$userId} ===" . PHP_EOL;
$itens = DB::table('faturas_cartao_itens')
    ->where('user_id', $userId)
    ->selectRaw('cartao_credito_id, COUNT(*) as qtd, SUM(valor) as total')
    ->groupBy('cartao_credito_id')
    ->get();

foreach ($itens as $i) {
    $cartao = $cartoes->firstWhere('id', $i->cartao_credito_id);
    $nomeCartao = $cartao ? $cartao->nome_cartao : 'Desconhecido';
    echo "  Cartão ID: {$i->cartao_credito_id} ({$nomeCartao}): {$i->qtd} itens - R$ " . number_format($i->total, 2, ',', '.') . PHP_EOL;
}
