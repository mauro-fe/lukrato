<?php
require_once __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

$userId = (int)($argv[1] ?? 1);

// Check lancamentos with conta_id=0
echo "=== LANCAMENTOS com conta_id=0 (user {$userId}) ===" . PHP_EOL;
$zero = DB::table('lancamentos')->where('user_id', $userId)->where('conta_id', 0)->count();
$zeroVal = DB::table('lancamentos')->where('user_id', $userId)->where('conta_id', 0)
    ->where('pago', 1)->where('afeta_caixa', 1)->sum('valor');
echo "Count: {$zero}, Sum pago+afeta: R$" . number_format($zeroVal, 2) . PHP_EOL;

// Check transfers
echo PHP_EOL . "=== TRANSFERENCIAS (user {$userId}) ===" . PHP_EOL;
$trans = DB::table('lancamentos')->where('user_id', $userId)->where('eh_transferencia', 1)
    ->get(['id', 'conta_id', 'conta_id_destino', 'tipo', 'valor', 'data', 'pago', 'afeta_caixa', 'origem_tipo']);
echo "Total: " . $trans->count() . PHP_EOL;
foreach ($trans as $t) {
    echo sprintf(
        "  #%d from=%d to=%d tipo=%s R$%.2f data=%s pago=%d afeta=%d origem=%s",
        $t->id,
        $t->conta_id,
        $t->conta_id_destino ?? 0,
        $t->tipo,
        $t->valor,
        $t->data,
        $t->pago,
        $t->afeta_caixa,
        $t->origem_tipo ?? 'null'
    ) . PHP_EOL;
}

// Check Pagar contas (#21) lancamentos in detail (the card account with -R$2857)
echo PHP_EOL . "=== DETALHES CONTA #21 (Pagar contas) ===" . PHP_EOL;
$lancs21 = DB::table('lancamentos')
    ->where('user_id', $userId)
    ->where(function ($q) {
        $q->where('conta_id', 21)->orWhere('conta_id_destino', 21);
    })
    ->where('pago', 1)->where('afeta_caixa', 1)
    ->orderBy('data')
    ->get(['id', 'conta_id', 'conta_id_destino', 'tipo', 'valor', 'data', 'descricao', 'origem_tipo', 'cartao_credito_id', 'eh_transferencia']);
echo "Total: " . $lancs21->count() . PHP_EOL;
foreach ($lancs21 as $l) {
    echo sprintf(
        "  #%d conta=%d dest=%s tipo=%-15s R$%.2f %s origem=%-20s cartao=%s eh_trans=%d | %s",
        $l->id,
        $l->conta_id,
        $l->conta_id_destino ?? '-',
        $l->tipo,
        $l->valor,
        $l->data,
        $l->origem_tipo ?? 'null',
        $l->cartao_credito_id ?? '-',
        $l->eh_transferencia,
        mb_substr($l->descricao, 0, 50)
    ) . PHP_EOL;
}

// Check if cartao #32 fatura items were linked to lancamentos
echo PHP_EOL . "=== FATURA ITEMS cartao #32 (Nubank) ===" . PHP_EOL;
$items = DB::table('faturas_cartao_itens')
    ->where('user_id', $userId)
    ->where('cartao_credito_id', 32)
    ->selectRaw("pago, lancamento_id IS NOT NULL as has_lanc, COUNT(*) as cnt, SUM(valor) as soma")
    ->groupBy('pago', DB::raw('lancamento_id IS NOT NULL'))
    ->get();
foreach ($items as $i) {
    echo sprintf(
        "  pago=%d has_lancamento=%d count=%d sum=R$%.2f",
        $i->pago,
        $i->has_lanc,
        $i->cnt,
        $i->soma
    ) . PHP_EOL;
}

// Compare descriptions between lancamentos on conta 21 and fatura items for card 32
echo PHP_EOL . "=== COMPARAR: Lancamentos conta 21 vs Fatura items cartao 32 ===" . PHP_EOL;
$lancsDesc = DB::table('lancamentos')
    ->where('user_id', $userId)
    ->where('conta_id', 21)
    ->where('origem_tipo', 'cartao_credito')
    ->whereNull('cartao_credito_id')
    ->pluck('descricao', 'id')->all();

$faturaDescs = DB::table('faturas_cartao_itens')
    ->where('user_id', $userId)
    ->where('cartao_credito_id', 32)
    ->pluck('descricao', 'id')->all();

$matches = 0;
$matchList = [];
foreach ($lancsDesc as $lid => $ldesc) {
    foreach ($faturaDescs as $fid => $fdesc) {
        if (strtolower(trim($ldesc)) === strtolower(trim($fdesc))) {
            $matches++;
            if (count($matchList) < 10) {
                $matchList[] = "  Lanc #{$lid} '{$ldesc}' = FatItem #{$fid} '{$fdesc}'";
            }
        }
    }
}
echo "Lancamentos sem cartao_id em conta 21: " . count($lancsDesc) . PHP_EOL;
echo "Fatura items cartao 32: " . count($faturaDescs) . PHP_EOL;
echo "Matches por descricao: {$matches}" . PHP_EOL;
foreach ($matchList as $m) echo $m . PHP_EOL;

echo PHP_EOL . "=== FIM ===" . PHP_EOL;
