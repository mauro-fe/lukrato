<?php
require_once __DIR__ . '/../bootstrap.php';

use Application\Models\FaturaCartaoItem;
use Application\Models\Lancamento;

echo "\n🔍 VERIFICANDO PAGAMENTOS DE FATURA\n";
echo "════════════════════════════════════════════════════════\n\n";

// Verificar itens pagos
$itensPagos = FaturaCartaoItem::where('pago', true)
    ->orderBy('data_pagamento', 'desc')
    ->limit(10)
    ->get();

echo "📋 Itens de fatura pagos: {$itensPagos->count()}\n\n";

if ($itensPagos->isEmpty()) {
    echo "❌ Nenhum item de fatura foi pago ainda.\n";
    echo "   Isso explica por que não há lançamentos de pagamento.\n\n";
    exit(0);
}

foreach ($itensPagos as $item) {
    echo "• ID {$item->id}: {$item->descricao} - R$ {$item->valor} (Pago em: {$item->data_pagamento})\n";
}

echo "\n";

// Verificar se há lançamentos correspondentes
echo "🔍 Verificando lançamentos de pagamento correspondentes...\n\n";

$dataPagamento = $itensPagos->first()->data_pagamento;
$lancamentosPagamento = Lancamento::whereDate('data', $dataPagamento)
    ->where('descricao', 'like', '%Pagamento%')
    ->get();

echo "Lançamentos com 'Pagamento' na data {$dataPagamento}: {$lancamentosPagamento->count()}\n\n";

if ($lancamentosPagamento->isEmpty()) {
    echo "❌ PROBLEMA CONFIRMADO!\n";
    echo "   Há itens pagos mas não há lançamentos correspondentes.\n\n";
} else {
    foreach ($lancamentosPagamento as $lanc) {
        echo "✅ ID {$lanc->id}: {$lanc->descricao} - R$ {$lanc->valor}\n";
    }
}
