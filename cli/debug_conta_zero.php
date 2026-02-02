<?php
require_once __DIR__ . '/../bootstrap.php';

use Application\Models\Lancamento;
use Illuminate\Database\Capsule\Manager as DB;

echo "=== LANÇAMENTOS COM CONTA_ID = 0 ===" . PHP_EOL;

$lancamentos = Lancamento::where('user_id', 1)
    ->where('conta_id', 0)
    ->where('data', '>=', '2026-01-01')
    ->where('data', '<=', '2026-01-31')
    ->orderBy('data')
    ->get();

foreach ($lancamentos as $l) {
    echo sprintf(
        "#%-5d %s | %-8s | R$ %8.2f | cartao_id=%s | %s",
        $l->id,
        $l->data,
        strtoupper($l->tipo),
        $l->valor,
        $l->cartao_credito_id ?: '-',
        substr($l->descricao, 0, 35)
    ) . PHP_EOL;
}

echo PHP_EOL . "Total com conta_id=0: " . count($lancamentos) . PHP_EOL;

echo PHP_EOL . "=== VERIFICANDO CARTÃO #32 NA TABELA cartoes_credito ===" . PHP_EOL;
$cartao = DB::table('cartoes_credito')->where('id', 32)->first();
if ($cartao) {
    echo "Cartão #32 existe: " . $cartao->nome . " | conta_pagamento_id: " . ($cartao->conta_pagamento_id ?? 'NULL') . PHP_EOL;
} else {
    echo "Cartão #32 NÃO ENCONTRADO na tabela cartoes_credito!" . PHP_EOL;
}

echo PHP_EOL . "=== TODOS OS CARTÕES DO USUÁRIO ===" . PHP_EOL;
$cartoes = DB::table('cartoes_credito')->where('user_id', 1)->get();
foreach ($cartoes as $c) {
    echo sprintf("#%d | %s | conta_pagamento: #%s", $c->id, $c->nome, $c->conta_pagamento_id ?? 'NULL') . PHP_EOL;
}

echo PHP_EOL . "=== QUAL CARTAO_CREDITO_ID ESTÁ NOS LANÇAMENTOS COM CONTA_ID=0? ===" . PHP_EOL;
$cartaoIds = Lancamento::where('user_id', 1)
    ->where('conta_id', 0)
    ->groupBy('cartao_credito_id')
    ->pluck('cartao_credito_id');

foreach ($cartaoIds as $cartaoId) {
    $count = Lancamento::where('user_id', 1)
        ->where('conta_id', 0)
        ->where('cartao_credito_id', $cartaoId)
        ->count();
    echo "cartao_credito_id = " . ($cartaoId ?: 'NULL') . " → " . $count . " lançamentos" . PHP_EOL;
}

echo PHP_EOL . "=== VERIFICANDO O QUE É ESSE #32 ===" . PHP_EOL;
// Talvez seja um ID de outra coisa
$conta32 = DB::table('contas')->where('id', 32)->first();
if ($conta32) {
    echo "Conta #32: " . $conta32->nome . " | user_id: " . $conta32->user_id . PHP_EOL;
} else {
    echo "Conta #32 não existe" . PHP_EOL;
}

// Verificar se existem cartoes com id diferente
echo PHP_EOL . "=== TODOS CARTOES (QUALQUER USUÁRIO) ===" . PHP_EOL;
$todosCartoes = DB::table('cartoes_credito')->get();
foreach ($todosCartoes as $c) {
    echo sprintf("#%d | user_id=%d | %s", $c->id, $c->user_id, $c->nome) . PHP_EOL;
}
