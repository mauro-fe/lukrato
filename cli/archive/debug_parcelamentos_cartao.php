<?php

require_once __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

echo "=== DEBUG: Parcelamentos de Cartão ===\n\n";

$userId = 1;

// Verificar lançamentos com eh_parcelado = true
echo "1. Lançamentos com eh_parcelado = true:\n";
$lancamentos = DB::table('lancamentos')
    ->where('user_id', $userId)
    ->where('eh_parcelado', true)
    ->get();

echo "Total: " . $lancamentos->count() . "\n\n";

foreach ($lancamentos as $lanc) {
    echo "ID: {$lanc->id} | {$lanc->descricao} | R$ {$lanc->valor}\n";
    echo "  parcelamento_id: " . ($lanc->parcelamento_id ?? 'NULL') . "\n";
    echo "  cartao_credito_id: " . ($lanc->cartao_credito_id ?? 'NULL') . "\n";
    echo "  parcela_atual: " . ($lanc->parcela_atual ?? 'NULL') . "\n";
    echo "\n";
}

// Verificar lançamentos de cartão sem parcelamento_id
echo "2. Lançamentos de cartão sem parcelamento_id:\n";
$lancamentosCartao = DB::table('lancamentos')
    ->where('user_id', $userId)
    ->where('eh_parcelado', true)
    ->whereNull('parcelamento_id')
    ->whereNotNull('cartao_credito_id')
    ->get();

echo "Total: " . $lancamentosCartao->count() . "\n\n";

foreach ($lancamentosCartao as $lanc) {
    echo "ID: {$lanc->id} | {$lanc->descricao} | R$ {$lanc->valor}\n";
}

echo "\n✅ Debug concluído!\n";
