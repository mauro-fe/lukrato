<?php

/**
 * Script para corrigir o campo afeta_caixa dos lançamentos
 * 
 * Regras:
 * - Lançamentos SEM cartao_credito_id: afeta_caixa = true (sempre afetam saldo)
 * - Lançamentos COM cartao_credito_id E pago = true: afeta_caixa = true (já foram pagos)
 * - Lançamentos COM cartao_credito_id E pago = false: afeta_caixa = false (ainda não afetam saldo)
 */
require __DIR__ . '/../bootstrap.php';

use Application\Models\Lancamento;
use Illuminate\Support\Facades\DB;

echo "=== Correção do campo afeta_caixa ===\n\n";

// Verificar estado atual
$total = Lancamento::count();
$afetaTrue = Lancamento::where('afeta_caixa', true)->count();
$afetaFalse = Lancamento::where('afeta_caixa', false)->count();
$afetaNull = Lancamento::whereNull('afeta_caixa')->count();

echo "Estado atual:\n";
echo "  Total: $total\n";
echo "  afeta_caixa = true: $afetaTrue\n";
echo "  afeta_caixa = false: $afetaFalse\n";
echo "  afeta_caixa = NULL: $afetaNull\n\n";

// Perguntar confirmação
echo "Deseja corrigir os dados? (s/n): ";
$handle = fopen("php://stdin", "r");
$line = fgets($handle);
if (trim($line) !== 's') {
    echo "Operação cancelada.\n";
    exit;
}

echo "\n--- Iniciando correção ---\n\n";

// 1. Lançamentos SEM cartão de crédito devem ter afeta_caixa = true
$semCartao = Lancamento::whereNull('cartao_credito_id')
    ->where(function ($q) {
        $q->whereNull('afeta_caixa')
            ->orWhere('afeta_caixa', false);
    })
    ->update(['afeta_caixa' => true]);
echo "1. Lançamentos sem cartão atualizados para afeta_caixa = true: $semCartao\n";

// 2. Lançamentos de cartão PAGOS devem ter afeta_caixa = true
$cartaoPago = Lancamento::whereNotNull('cartao_credito_id')
    ->where('pago', true)
    ->where(function ($q) {
        $q->whereNull('afeta_caixa')
            ->orWhere('afeta_caixa', false);
    })
    ->update(['afeta_caixa' => true]);
echo "2. Lançamentos de cartão PAGOS atualizados para afeta_caixa = true: $cartaoPago\n";

// 3. Lançamentos de cartão NÃO PAGOS devem ter afeta_caixa = false
$cartaoNaoPago = Lancamento::whereNotNull('cartao_credito_id')
    ->where('pago', false)
    ->where(function ($q) {
        $q->whereNull('afeta_caixa')
            ->orWhere('afeta_caixa', true);
    })
    ->update(['afeta_caixa' => false]);
echo "3. Lançamentos de cartão NÃO PAGOS atualizados para afeta_caixa = false: $cartaoNaoPago\n";

// Verificar estado final
echo "\n--- Estado final ---\n";
$afetaTrue = Lancamento::where('afeta_caixa', true)->count();
$afetaFalse = Lancamento::where('afeta_caixa', false)->count();
$afetaNull = Lancamento::whereNull('afeta_caixa')->count();

echo "  afeta_caixa = true: $afetaTrue\n";
echo "  afeta_caixa = false: $afetaFalse\n";
echo "  afeta_caixa = NULL: $afetaNull\n\n";

echo "✅ Correção concluída!\n";
