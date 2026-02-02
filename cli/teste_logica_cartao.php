<?php

/**
 * TESTE: Verificar lógica do sistema de cartão de crédito
 * 
 * Fluxo esperado:
 * 1. Criar lançamento no cartão → pago=0, conta_id=NULL, afeta_caixa=0
 * 2. Fatura é gerada no mês correto (mês da compra)
 * 3. Ao pagar fatura → pago=1, conta_id=conta_pagamento, afeta_caixa=1
 */

require_once __DIR__ . '/../bootstrap.php';

use Application\Models\Lancamento;
use Illuminate\Database\Capsule\Manager as DB;

echo "=== TESTE DA LÓGICA DO CARTÃO DE CRÉDITO ===\n\n";

$userId = 1;

// 1. Verificar lançamentos pendentes de cartão (devem estar sem conta_id)
echo "1. LANÇAMENTOS PENDENTES DE CARTÃO:\n";
$pendentes = Lancamento::where('user_id', $userId)
    ->whereNotNull('cartao_credito_id')
    ->where('pago', 0)
    ->get();

echo "   Total: {$pendentes->count()}\n";
$comConta = $pendentes->filter(fn($l) => !empty($l->conta_id))->count();
$semConta = $pendentes->filter(fn($l) => empty($l->conta_id))->count();
echo "   Com conta_id: {$comConta} " . ($comConta > 0 ? "❌ ERRADO" : "✅ OK") . "\n";
echo "   Sem conta_id: {$semConta} " . ($semConta == $pendentes->count() ? "✅ OK" : "❌ ERRADO") . "\n";

// Verificar afeta_caixa dos pendentes
$pendentesAfetaCaixa = $pendentes->filter(fn($l) => $l->afeta_caixa == 1)->count();
echo "   Com afeta_caixa=1: {$pendentesAfetaCaixa} " . ($pendentesAfetaCaixa > 0 ? "❌ ERRADO (deveria ser 0)" : "✅ OK") . "\n";

// 2. Verificar lançamentos pagos de cartão (devem estar com conta_id)
echo "\n2. LANÇAMENTOS PAGOS DE CARTÃO:\n";
$pagos = Lancamento::where('user_id', $userId)
    ->whereNotNull('cartao_credito_id')
    ->where('pago', 1)
    ->get();

echo "   Total: {$pagos->count()}\n";
$pagosComConta = $pagos->filter(fn($l) => !empty($l->conta_id))->count();
$pagosSemConta = $pagos->filter(fn($l) => empty($l->conta_id))->count();
echo "   Com conta_id: {$pagosComConta} " . ($pagosComConta == $pagos->count() ? "✅ OK" : "⚠️ Alguns sem conta") . "\n";
echo "   Sem conta_id: {$pagosSemConta} " . ($pagosSemConta > 0 ? "⚠️ ATENÇÃO" : "✅ OK") . "\n";

// 3. Verificar itens de fatura pendentes
echo "\n3. ITENS DE FATURA PENDENTES:\n";
$itensPendentes = DB::table('faturas_cartao_itens')
    ->where('user_id', $userId)
    ->where('pago', 0)
    ->get();

echo "   Total: {$itensPendentes->count()}\n";
if ($itensPendentes->count() > 0) {
    $porMes = $itensPendentes->groupBy(function ($i) {
        return sprintf('%04d-%02d', $i->ano_referencia ?? 0, $i->mes_referencia ?? 0);
    });
    foreach ($porMes as $mes => $itens) {
        $soma = $itens->sum('valor');
        echo "   {$mes}: {$itens->count()} itens | R$ " . number_format($soma, 2, ',', '.') . "\n";
    }
}

// 4. Verificar itens de fatura pagos
echo "\n4. ITENS DE FATURA PAGOS:\n";
$itensPagos = DB::table('faturas_cartao_itens')
    ->where('user_id', $userId)
    ->where('pago', 1)
    ->get();

echo "   Total: {$itensPagos->count()}\n";
if ($itensPagos->count() > 0) {
    $porMes = $itensPagos->groupBy(function ($i) {
        return sprintf('%04d-%02d', $i->ano_referencia ?? 0, $i->mes_referencia ?? 0);
    });
    foreach ($porMes as $mes => $itens) {
        $soma = $itens->sum('valor');
        echo "   {$mes}: {$itens->count()} itens | R$ " . number_format($soma, 2, ',', '.') . "\n";
    }
}

// 5. Resumo
echo "\n=== RESUMO ===\n";
$problemas = 0;
if ($comConta > 0) {
    echo "❌ Pendentes com conta_id (deveria ser NULL)\n";
    $problemas++;
}
if ($pendentesAfetaCaixa > 0) {
    echo "❌ Pendentes com afeta_caixa=1 (deveria ser 0)\n";
    $problemas++;
}
if ($pagosSemConta > 0) {
    echo "⚠️ Pagos sem conta_id (deveria ter conta)\n";
}
if ($problemas == 0) {
    echo "✅ Lógica do sistema está correta!\n";
}
