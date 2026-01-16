<?php
require __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

$items = DB::table('faturas_cartao_itens')
    ->where('cartao_credito_id', 36)
    ->select('id', 'descricao', 'valor', 'mes_referencia', 'ano_referencia', 'pago')
    ->orderByDesc('ano_referencia')
    ->orderByDesc('mes_referencia')
    ->limit(15)
    ->get();

echo "=== ITENS DO CARTÃO 36 ===\n\n";
foreach ($items as $item) {
    echo sprintf(
        "ID: %d | %s | R$ %.2f | Ref: %02d/%d | Pago: %s\n",
        $item->id,
        substr($item->descricao, 0, 40),
        $item->valor,
        $item->mes_referencia,
        $item->ano_referencia,
        $item->pago ? 'SIM' : 'NÃO'
    );
}

echo "\n=== TOTAIS POR MÊS ===\n\n";
$totais = DB::table('faturas_cartao_itens')
    ->where('cartao_credito_id', 36)
    ->select(
        DB::raw('mes_referencia, ano_referencia, SUM(valor) as total, COUNT(*) as qtd')
    )
    ->groupBy('mes_referencia', 'ano_referencia')
    ->orderByDesc('ano_referencia')
    ->orderByDesc('mes_referencia')
    ->get();

foreach ($totais as $t) {
    echo sprintf(
        "%02d/%d: R$ %.2f (%d itens)\n",
        $t->mes_referencia,
        $t->ano_referencia,
        $t->total,
        $t->qtd
    );
}
