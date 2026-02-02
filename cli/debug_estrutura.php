<?php
require_once __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

echo "=== ESTRUTURA DA TABELA cartoes_credito ===" . PHP_EOL;
$cols = DB::select('DESCRIBE cartoes_credito');
foreach ($cols as $c) {
    echo $c->Field . ' | ' . $c->Type . PHP_EOL;
}

echo PHP_EOL . "=== CARTÃO #32 COMPLETO ===" . PHP_EOL;
$cartao = DB::table('cartoes_credito')->where('id', 32)->first();
if ($cartao) {
    print_r($cartao);
}

echo PHP_EOL . "=== LANÇAMENTOS DE JANEIRO - CONTA_ID QUE NÃO EXISTE ===" . PHP_EOL;
$lancamentos = DB::table('lancamentos')
    ->where('user_id', 1)
    ->where('data', '>=', '2026-01-01')
    ->where('data', '<=', '2026-01-31')
    ->get();

$contasExistentes = DB::table('contas')->where('user_id', 1)->pluck('id')->toArray();

$contaInexistente = [];
foreach ($lancamentos as $l) {
    if ($l->conta_id && !in_array($l->conta_id, $contasExistentes)) {
        $contaInexistente[] = $l;
    }
}

echo "Lançamentos com conta_id que não existe:" . PHP_EOL;
foreach ($contaInexistente as $l) {
    echo sprintf(
        "#%-5d %s | %-8s | R$ %8.2f | conta_id=%d | %s",
        $l->id,
        $l->data,
        strtoupper($l->tipo),
        $l->valor,
        $l->conta_id,
        substr($l->descricao, 0, 30)
    ) . PHP_EOL;
}
echo "Total: " . count($contaInexistente) . PHP_EOL;

echo PHP_EOL . "=== RESUMO POR CONTA_ID ===" . PHP_EOL;
$resumo = DB::table('lancamentos')
    ->select('conta_id', DB::raw('COUNT(*) as qtd'))
    ->where('user_id', 1)
    ->where('data', '>=', '2026-01-01')
    ->where('data', '<=', '2026-01-31')
    ->groupBy('conta_id')
    ->get();

foreach ($resumo as $r) {
    $existe = in_array($r->conta_id, $contasExistentes) ? 'EXISTE' : 'NÃO EXISTE';
    echo sprintf("conta_id=%d | %d lançamentos | Conta %s", $r->conta_id, $r->qtd, $existe) . PHP_EOL;
}
