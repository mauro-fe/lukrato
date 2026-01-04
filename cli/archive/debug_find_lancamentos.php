<?php
require __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

$parcelaId = $argv[1] ?? 575;
$today = date('Y-m-d');

echo "Procurando lancamento parcela id={$parcelaId}\n";

$par = DB::table('lancamentos')->where('id', $parcelaId)->first();
if ($par) {
    echo "Parcela encontrada: id={$par->id} data={$par->data} pago={$par->pago} data_pagamento={$par->data_pagamento} descricao={$par->descricao}\n";
} else {
    echo "Parcela id={$parcelaId} nao encontrada\n";
}

echo "\nLancamentos com descricao like 'Pagamento antecipado%' e hoje:\n";
$rows = DB::table('lancamentos')
    ->where('descricao', 'like', 'Pagamento antecipado:%')
    ->orWhere('descricao', 'like', 'Pagamento antecipado%')
    ->where('data', $today)
    ->get();

foreach ($rows as $r) {
    echo "id={$r->id} data={$r->data} descricao={$r->descricao} valor={$r->valor} user_id={$r->user_id}\n";
}

echo "\nLancamentos com data_pagamento = hoje:\n";
$rows2 = DB::table('lancamentos')->where('data_pagamento', $today)->get();
foreach ($rows2 as $r) {
    echo "id={$r->id} data={$r->data} data_pagamento={$r->data_pagamento} descricao={$r->descricao} valor={$r->valor}\n";
}

echo "\nLancamentos do parcelamento da parcela (se existir):\n";
if ($par && isset($par->parcelamento_id) && $par->parcelamento_id) {
    $pid = $par->parcelamento_id;
    $rows3 = DB::table('lancamentos')->where('parcelamento_id', $pid)->orderBy('data')->get();
    foreach ($rows3 as $r) {
        echo "id={$r->id} data={$r->data} pago={$r->pago} data_pagamento={$r->data_pagamento} descricao={$r->descricao}\n";
    }
} else {
    echo "parcelamento_id nao disponivel na parcela\n";
}

echo "\nSimular listagem geral (com filtro aplicado igual ao controller) para dezembro e janeiro:\n";
$decRows = DB::table('lancamentos as l')
    ->whereBetween('l.data', ['2025-12-01', '2025-12-31'])
    ->where(function ($w) {
        $w->whereNull('l.parcelamento_id')->orWhere('l.pago', 0);
    })
    ->get();
echo "-- Dezembro --\n";
foreach ($decRows as $r) {
    echo "id={$r->id} data={$r->data} descricao={$r->descricao} pago={$r->pago} parcelamento_id={$r->parcelamento_id}\n";
}

$janRows = DB::table('lancamentos as l')
    ->whereBetween('l.data', ['2026-01-01', '2026-01-31'])
    ->where(function ($w) {
        $w->whereNull('l.parcelamento_id')->orWhere('l.pago', 0);
    })
    ->get();
echo "-- Janeiro --\n";
foreach ($janRows as $r) {
    echo "id={$r->id} data={$r->data} descricao={$r->descricao} pago={$r->pago} parcelamento_id={$r->parcelamento_id}\n";
}
