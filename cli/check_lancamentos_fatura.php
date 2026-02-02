<?php
require __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

echo "=== VERIFICAÇÃO DE LANÇAMENTOS DE FATURA ===\n\n";

// Buscar lançamentos com origem_tipo = pagamento_fatura
echo "--- Lançamentos tipo 'pagamento_fatura' ---\n";
$lancamentosFatura = DB::table('lancamentos')
    ->where('origem_tipo', 'pagamento_fatura')
    ->orWhere('descricao', 'LIKE', '%Pagamento Fatura%')
    ->orderBy('created_at', 'desc')
    ->limit(20)
    ->get();

if (count($lancamentosFatura) > 0) {
    foreach ($lancamentosFatura as $l) {
        echo "ID: {$l->id} | {$l->data} | R$ " . number_format($l->valor, 2, ',', '.') . " | {$l->descricao}\n";
        echo "  Tipo: {$l->tipo} | Pago: " . ($l->pago ? 'Sim' : 'Não') . " | Origem: {$l->origem_tipo}\n";
        echo "  Conta: {$l->conta_id} | Cartão: {$l->cartao_credito_id}\n\n";
    }
} else {
    echo "⚠️ Nenhum lançamento de pagamento de fatura encontrado.\n\n";
}

// Verificar últimas faturas pagas
echo "\n--- Faturas com status 'paga' ---\n";
$faturasPagas = DB::table('faturas')
    ->where('status', 'paga')
    ->orderBy('updated_at', 'desc')
    ->limit(10)
    ->get();

foreach ($faturasPagas as $f) {
    echo "Fatura ID: {$f->id} | {$f->descricao} | R$ " . number_format($f->valor_total, 2, ',', '.') . " | Cartão: {$f->cartao_credito_id}\n";
}

// Verificar itens pagos recentemente
echo "\n--- Últimos itens marcados como pagos ---\n";
$itensPagos = DB::table('faturas_cartao_itens')
    ->where('pago', true)
    ->whereNotNull('data_pagamento')
    ->orderBy('data_pagamento', 'desc')
    ->limit(10)
    ->get();

foreach ($itensPagos as $i) {
    $lancamentoInfo = $i->lancamento_id ? "Lanc: {$i->lancamento_id}" : "Sem lançamento vinculado";
    echo "Item {$i->id}: {$i->descricao} | Pago: {$i->data_pagamento} | {$lancamentoInfo}\n";
}

// Verificar colunas da tabela lançamentos
echo "\n--- Verificando coluna origem_tipo ---\n";
$columns = DB::select("SHOW COLUMNS FROM lancamentos LIKE 'origem_tipo'");
if (empty($columns)) {
    echo "⚠️ Coluna 'origem_tipo' NÃO EXISTE na tabela lancamentos!\n";
    echo "Isso pode explicar porque o lançamento não está sendo identificado corretamente.\n";
} else {
    echo "✅ Coluna 'origem_tipo' existe.\n";
}
