<?php
require __DIR__ . '/../bootstrap.php';

use Application\Models\FaturaCartaoItem;

echo "=== Itens com parcela_atual ===\n";
$count = FaturaCartaoItem::whereNotNull('parcela_atual')->count();
echo "Com parcela_atual: $count\n";

$count2 = FaturaCartaoItem::whereNull('parcela_atual')->count();
echo "Sem parcela_atual: $count2\n";

$count3 = FaturaCartaoItem::where('parcela_atual', 1)->count();
echo "Com parcela_atual = 1: $count3\n";

echo "\n=== Amostra de itens ===\n";
$itens = FaturaCartaoItem::select('id', 'fatura_id', 'parcela_atual', 'mes_referencia', 'ano_referencia')
    ->limit(15)
    ->get();

foreach ($itens as $i) {
    echo "Item #{$i->id} - Fatura #{$i->fatura_id} - Parcela: " . ($i->parcela_atual ?? 'NULL') . " - {$i->mes_referencia}/{$i->ano_referencia}\n";
}
