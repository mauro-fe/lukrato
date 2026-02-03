<?php
require __DIR__ . '/../bootstrap.php';

use Application\Models\FaturaCartaoItem;
use Application\Models\Fatura;

echo "=== Itens do cartão 32 ===\n\n";

$itens = FaturaCartaoItem::where('cartao_credito_id', 32)->orderBy('mes_referencia')->get();
foreach ($itens as $i) {
    $pago = $i->pago ? 'PAGO' : 'NAO_PAGO';
    echo "ID:{$i->id} | {$i->mes_referencia}/{$i->ano_referencia} | {$pago} | fatura:{$i->fatura_id} | {$i->descricao}\n";
}

echo "\n=== Faturas do cartão 32 ===\n\n";
$faturas = Fatura::where('cartao_credito_id', 32)->get();
foreach ($faturas as $f) {
    echo "ID:{$f->id} | Status:{$f->status} | Total:{$f->valor_total} | {$f->descricao}\n";
}
