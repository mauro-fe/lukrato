<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use Application\Models\FaturaCartaoItem;
use Illuminate\Database\Capsule\Manager as DB;

echo "=== ESTRUTURA faturas_cartao_itens ===\n";
$cols = DB::select('DESCRIBE faturas_cartao_itens');
foreach ($cols as $col) {
    echo "  {$col->Field} ({$col->Type})\n";
}

echo "\n=== PARCELAMENTOS (itens com total_parcelas > 1) ===\n";
$itens = FaturaCartaoItem::whereRaw('total_parcelas > 1')
    ->select('id', 'descricao', 'parcela_atual', 'total_parcelas', 'item_pai_id', 'user_id')
    ->orderBy('id', 'desc')
    ->limit(20)
    ->get();

foreach ($itens as $i) {
    $itemPaiIdDisplay = $i->item_pai_id ?? 'null';
    echo "  ID: {$i->id}, Desc: {$i->descricao}, Parcela: {$i->parcela_atual}/{$i->total_parcelas}, item_pai_id: {$itemPaiIdDisplay}\n";
}

echo "\n=== EXEMPLO: PARCELAS 1 SEM ITEM_PAI_ID ===\n";
$semPai = FaturaCartaoItem::whereRaw('total_parcelas > 1')
    ->whereNull('item_pai_id')
    ->where('parcela_atual', '=', 1)
    ->select('id', 'descricao', 'parcela_atual', 'total_parcelas')
    ->limit(5)
    ->get();

foreach ($semPai as $i) {
    $filhos = FaturaCartaoItem::where('item_pai_id', $i->id)->count();
    echo "  ID: {$i->id}, Desc: {$i->descricao}, Parcela 1/{$i->total_parcelas}, Filhos encontrados: {$filhos}\n";
}

echo "\n=== PARCELAS 2+ COM ITEM_PAI_ID ===\n";
$comPai = FaturaCartaoItem::whereRaw('total_parcelas > 1')
    ->whereNotNull('item_pai_id')
    ->where('parcela_atual', '>', 1)
    ->select('id', 'descricao', 'parcela_atual', 'total_parcelas', 'item_pai_id')
    ->limit(5)
    ->get();

foreach ($comPai as $i) {
    echo "  ID: {$i->id}, Desc: {$i->descricao}, Parcela {$i->parcela_atual}/{$i->total_parcelas}, item_pai_id: {$i->item_pai_id}\n";
}
