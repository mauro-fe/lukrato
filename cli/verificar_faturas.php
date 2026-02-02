<?php
require __DIR__ . '/../bootstrap.php';

use Application\Models\FaturaCartaoItem;
use Application\Models\Lancamento;
use Illuminate\Database\Capsule\Manager as DB;

echo "=== FATURAS POR MES/ANO (via faturas_cartao_itens) ===" . PHP_EOL;
$faturas = DB::table('faturas_cartao_itens')
    ->select(
        'mes_referencia',
        'ano_referencia',
        DB::raw('COUNT(*) as total_itens'),
        DB::raw('SUM(CASE WHEN pago = 1 THEN 1 ELSE 0 END) as itens_pagos'),
        DB::raw('SUM(CASE WHEN pago = 0 THEN 1 ELSE 0 END) as itens_pendentes'),
        DB::raw('SUM(CASE WHEN pago = 1 THEN valor ELSE 0 END) as valor_pago'),
        DB::raw('SUM(CASE WHEN pago = 0 THEN valor ELSE 0 END) as valor_pendente')
    )
    ->groupBy('ano_referencia', 'mes_referencia')
    ->orderBy('ano_referencia')
    ->orderBy('mes_referencia')
    ->get();

foreach ($faturas as $f) {
    $status = $f->itens_pendentes == 0 ? 'PAGA' : 'PENDENTE';
    echo sprintf(
        "%02d/%d - %s | Itens: %d (pagos: %d, pend: %d) | Pago: R$ %.2f | Pendente: R$ %.2f",
        $f->mes_referencia,
        $f->ano_referencia,
        $status,
        $f->total_itens,
        $f->itens_pagos,
        $f->itens_pendentes,
        $f->valor_pago,
        $f->valor_pendente
    ) . PHP_EOL;
}

echo PHP_EOL . "=== ITENS DA FATURA 01/2026 (PAGA no front) ===" . PHP_EOL;
$itens01 = FaturaCartaoItem::where('mes_referencia', 1)
    ->where('ano_referencia', 2026)
    ->orderBy('id')
    ->get();
echo "Total itens: " . $itens01->count() . PHP_EOL;
foreach ($itens01 as $item) {
    $lanc = Lancamento::find($item->lancamento_id);
    $dataCompra = $lanc ? $lanc->data : 'N/A';
    echo sprintf(
        "  Item %d - R$ %.2f | Pago: %s | Data compra lançamento: %s",
        $item->id,
        $item->valor,
        $item->pago ? 'Sim' : 'Não',
        $dataCompra
    ) . PHP_EOL;
}

echo PHP_EOL . "=== ITENS DA FATURA 02/2026 (PENDENTE R$ 724,64 no front) ===" . PHP_EOL;
$itens02 = FaturaCartaoItem::where('mes_referencia', 2)
    ->where('ano_referencia', 2026)
    ->orderBy('id')
    ->get();
echo "Total itens: " . $itens02->count() . PHP_EOL;
foreach ($itens02 as $item) {
    $lanc = Lancamento::find($item->lancamento_id);
    $dataCompra = $lanc ? $lanc->data : 'N/A';
    echo sprintf(
        "  Item %d - R$ %.2f | Pago: %s | Data compra lançamento: %s",
        $item->id,
        $item->valor,
        $item->pago ? 'Sim' : 'Não',
        $dataCompra
    ) . PHP_EOL;
}

echo PHP_EOL . "=== ANÁLISE: COMPRAS DE DEZEMBRO 2025 ===" . PHP_EOL;
$comprasDez = Lancamento::where('cartao_credito_id', '!=', null)
    ->whereRaw("DATE_FORMAT(data, '%Y-%m') = '2025-12'")
    ->orderBy('data')
    ->get();

echo "Total de compras em 12/2025: " . $comprasDez->count() . PHP_EOL;
foreach ($comprasDez as $l) {
    $item = FaturaCartaoItem::where('lancamento_id', $l->id)->first();
    $faturaRef = 'Sem item de fatura';
    if ($item) {
        $faturaRef = sprintf(
            "Item fatura %02d/%d (pago: %s)",
            $item->mes_referencia ?? 0,
            $item->ano_referencia ?? 0,
            $item->pago ? 'Sim' : 'Não'
        );
    }
    echo sprintf(
        "  Lanc %d - %s | R$ %.2f | Lanc.pago: %s | %s",
        $l->id,
        $l->data,
        $l->valor,
        $l->pago ? 'Sim' : 'Não',
        $faturaRef
    ) . PHP_EOL;
}

echo PHP_EOL . "=== CONCLUSÃO ===" . PHP_EOL;
echo "O frontend usa mes_referencia/ano_referencia de faturas_cartao_itens para agrupar." . PHP_EOL;
echo "Se compras de 12/2025 estão com mes_referencia=1/2026 ou 2/2026, o problema está nos DADOS." . PHP_EOL;
echo "Se as compras de 12/2025 deveriam estar em fatura 12/2025, precisa corrigir mes_referencia." . PHP_EOL;
