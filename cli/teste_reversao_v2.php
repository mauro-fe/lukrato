<?php

/**
 * Teste de reversão de pagamento de fatura
 * Simula a lógica do reverterPagamentoFatura
 */
require_once __DIR__ . '/../bootstrap.php';

use Application\Models\Lancamento;
use Application\Models\Fatura;
use Application\Models\FaturaCartaoItem;

$userId = 1;
$cartaoId = 32;

echo "=== Verificar itens das faturas 9/2026 e 10/2026 ===\n\n";

// Fatura 9/2026 = vencimento setembro
$itensFatura9 = FaturaCartaoItem::where('cartao_credito_id', $cartaoId)
    ->where('user_id', $userId)
    ->whereYear('data_vencimento', 2026)
    ->whereMonth('data_vencimento', 9)
    ->get();

echo "Fatura 9/2026 (vencimento setembro):\n";
foreach ($itensFatura9 as $item) {
    $status = $item->pago ? 'PAGO' : 'PENDENTE';
    echo "  ID:{$item->id} | {$status} | venc:{$item->data_vencimento->format('Y-m-d')} | {$item->descricao}\n";
}

// Fatura 10/2026 = vencimento outubro
$itensFatura10 = FaturaCartaoItem::where('cartao_credito_id', $cartaoId)
    ->where('user_id', $userId)
    ->whereYear('data_vencimento', 2026)
    ->whereMonth('data_vencimento', 10)
    ->get();

echo "\nFatura 10/2026 (vencimento outubro):\n";
foreach ($itensFatura10 as $item) {
    $status = $item->pago ? 'PAGO' : 'PENDENTE';
    echo "  ID:{$item->id} | {$status} | venc:{$item->data_vencimento->format('Y-m-d')} | {$item->descricao}\n";
}

echo "\n=== Simular reversão - Fatura 09/2026 ===\n\n";

// Simular observação do lançamento: "1 item(s) pago(s) - Fatura 09/2026"
$observacao = "1 item(s) pago(s) - Fatura 09/2026";

if (preg_match('/Fatura (\d{2})\/(\d{4})/', $observacao, $matches)) {
    $mes = (int)$matches[1];
    $ano = (int)$matches[2];
    echo "Mês/Ano extraído da observação: {$mes}/{$ano}\n";

    $itensPagos = FaturaCartaoItem::where('cartao_credito_id', $cartaoId)
        ->where('user_id', $userId)
        ->whereYear('data_vencimento', $ano)
        ->whereMonth('data_vencimento', $mes)
        ->where('pago', true)
        ->get();

    echo "Itens pagos a reverter: {$itensPagos->count()}\n";
    foreach ($itensPagos as $item) {
        echo "  ID:{$item->id} | {$item->descricao}\n";
    }
}

echo "\n=== Simular reversão - Fatura 10/2026 ===\n\n";

$observacao = "1 item(s) pago(s) - Fatura 10/2026";

if (preg_match('/Fatura (\d{2})\/(\d{4})/', $observacao, $matches)) {
    $mes = (int)$matches[1];
    $ano = (int)$matches[2];
    echo "Mês/Ano extraído da observação: {$mes}/{$ano}\n";

    $itensPagos = FaturaCartaoItem::where('cartao_credito_id', $cartaoId)
        ->where('user_id', $userId)
        ->whereYear('data_vencimento', $ano)
        ->whereMonth('data_vencimento', $mes)
        ->where('pago', true)
        ->get();

    echo "Itens pagos a reverter: {$itensPagos->count()}\n";
    foreach ($itensPagos as $item) {
        echo "  ID:{$item->id} | {$item->descricao}\n";
    }
}

echo "\n=== Verificar lançamentos de pagamento de fatura existentes ===\n\n";

$lancamentos = Lancamento::where('user_id', $userId)
    ->where('origem_tipo', 'pagamento_fatura')
    ->where('cartao_credito_id', $cartaoId)
    ->orderBy('data')
    ->get();

foreach ($lancamentos as $l) {
    echo "ID:{$l->id} | {$l->data} | R\${$l->valor} | {$l->observacao}\n";
}
