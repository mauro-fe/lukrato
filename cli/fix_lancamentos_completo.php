<?php

/**
 * Script para corrigir TODOS os lançamentos com cartao_credito_id errado
 * 
 * Correções:
 * 1. Remover cartao_credito_id de TRANSFERÊNCIAS
 * 2. Remover cartao_credito_id de DESPESAS que não têm item de fatura vinculado
 * 3. Definir afeta_caixa = true e pago = true para esses lançamentos
 */
require __DIR__ . '/../bootstrap.php';

use Application\Models\Lancamento;
use Application\Models\FaturaCartaoItem;

echo "=== Correção completa de lançamentos ===\n\n";

// 1. Corrigir TRANSFERÊNCIAS com cartão
$transferenciasComCartao = Lancamento::where('tipo', 'transferencia')
    ->whereNotNull('cartao_credito_id')
    ->count();
echo "Transferências com cartao_credito_id: $transferenciasComCartao\n";

// 2. Identificar despesas que NÃO têm item de fatura vinculado (não são de cartão)
$despesasComCartao = Lancamento::where('tipo', 'despesa')
    ->whereNotNull('cartao_credito_id')
    ->count();
echo "Despesas com cartao_credito_id: $despesasComCartao\n";

// IDs de lançamentos que têm item de fatura vinculado (são realmente de cartão)
$idsComFatura = FaturaCartaoItem::whereNotNull('lancamento_id')
    ->pluck('lancamento_id')
    ->toArray();
echo "Lançamentos com item de fatura vinculado: " . count($idsComFatura) . "\n";

$despesasSemFatura = Lancamento::where('tipo', 'despesa')
    ->whereNotNull('cartao_credito_id')
    ->whereNotIn('id', $idsComFatura)
    ->count();
echo "Despesas com cartão MAS SEM item de fatura: $despesasSemFatura\n\n";

$total = $transferenciasComCartao + $despesasSemFatura;
echo "Total a corrigir: $total\n\n";

if ($total === 0) {
    echo "✅ Nada para corrigir!\n";
    exit;
}

echo "Iniciando correção...\n\n";

// Corrigir transferências
$corrigidosTransf = Lancamento::where('tipo', 'transferencia')
    ->whereNotNull('cartao_credito_id')
    ->update([
        'cartao_credito_id' => null,
        'afeta_caixa' => true,
    ]);
echo "1. Transferências corrigidas: $corrigidosTransf\n";

// Corrigir despesas sem item de fatura
$corrigidosDesp = Lancamento::where('tipo', 'despesa')
    ->whereNotNull('cartao_credito_id')
    ->whereNotIn('id', $idsComFatura)
    ->update([
        'cartao_credito_id' => null,
        'afeta_caixa' => true,
        'pago' => true,
    ]);
echo "2. Despesas sem fatura corrigidas: $corrigidosDesp\n";

// Estado final
echo "\n--- Estado final ---\n";
$semCartao = Lancamento::whereNull('cartao_credito_id')->count();
$comCartao = Lancamento::whereNotNull('cartao_credito_id')->count();
echo "SEM cartao_credito_id: $semCartao\n";
echo "COM cartao_credito_id: $comCartao\n";

$afetaTrue = Lancamento::where('afeta_caixa', true)->count();
$afetaFalse = Lancamento::where('afeta_caixa', false)->count();
echo "afeta_caixa = true: $afetaTrue\n";
echo "afeta_caixa = false: $afetaFalse\n";

echo "\n✅ Correção concluída!\n";
