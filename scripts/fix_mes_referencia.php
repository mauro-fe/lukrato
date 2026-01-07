<?php

require __DIR__ . '/../bootstrap.php';

use Application\Models\FaturaCartaoItem;

$itens = FaturaCartaoItem::whereNull('mes_referencia')
    ->orWhereNull('ano_referencia')
    ->get();

echo "Itens sem mes/ano: {$itens->count()}\n";

foreach ($itens as $item) {
    $data = new DateTime($item->data_vencimento);
    $item->mes_referencia = (int)$data->format('m');
    $item->ano_referencia = (int)$data->format('Y');
    $item->save();

    echo "  ✓ Item #{$item->id}: {$item->mes_referencia}/{$item->ano_referencia}\n";
}

echo "\n✅ Atualizados com sucesso!\n";
