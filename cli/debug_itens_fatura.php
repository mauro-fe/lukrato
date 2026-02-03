<?php
require __DIR__ . '/../bootstrap.php';

use Application\Models\FaturaCartaoItem;

$itens = FaturaCartaoItem::where('cartao_credito_id', 32)
    ->where('mes_referencia', 8)
    ->where('ano_referencia', 2026)
    ->get();

echo "Itens fatura Ago/2026 cartão 32: " . $itens->count() . "\n";
foreach ($itens as $i) {
    $pago = $i->pago ? 'PAGO' : 'NAO_PAGO';
    echo "  ID:{$i->id} | {$pago} | fatura_id:{$i->fatura_id} | {$i->descricao}\n";
}
