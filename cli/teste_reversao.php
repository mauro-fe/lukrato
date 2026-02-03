<?php
require __DIR__ . '/../bootstrap.php';

use Application\Models\Lancamento;
use Application\Models\FaturaCartaoItem;

echo "=== Verificar itens 62 e 63 ===\n\n";

$itens = FaturaCartaoItem::whereIn('id', [62, 63])->get();

foreach ($itens as $i) {
    $pago = $i->pago ? 'PAGO' : 'NAO_PAGO';
    echo "ID:{$i->id} | {$pago} | data_pagamento:{$i->data_pagamento} | {$i->descricao}\n";
}

echo "\n=== Simular busca da reversão ===\n";
$lancamento = Lancamento::find(1130);
if ($lancamento) {
    $dataPagamento = $lancamento->data_pagamento ?? $lancamento->data;
    echo "Buscando itens com data_pagamento = {$dataPagamento}\n\n";

    $itensPagos = FaturaCartaoItem::where('cartao_credito_id', $lancamento->cartao_credito_id)
        ->where('user_id', $lancamento->user_id)
        ->where('pago', true)
        ->whereDate('data_pagamento', $dataPagamento)
        ->get();

    echo "Itens encontrados: {$itensPagos->count()}\n";
    foreach ($itensPagos as $i) {
        echo "  ID:{$i->id} | {$i->descricao}\n";
    }
}
