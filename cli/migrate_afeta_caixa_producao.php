<?php

/**
 * ============================================================================
 * SCRIPT DE MIGRAÇÃO PARA PRODUÇÃO
 * ============================================================================
 * 
 * Este script corrige os lançamentos com cartao_credito_id incorreto e
 * ajusta o campo afeta_caixa.
 * 
 * EXECUTAR EM PRODUÇÃO: php cli/migrate_afeta_caixa_producao.php
 * 
 * Correções:
 * 1. Remover cartao_credito_id de RECEITAS
 * 2. Remover cartao_credito_id de TRANSFERÊNCIAS
 * 3. Remover cartao_credito_id de DESPESAS que NÃO têm item de fatura vinculado
 * 4. Definir afeta_caixa = true para lançamentos normais
 * 5. Manter afeta_caixa = false para lançamentos de cartão não pagos
 */

require __DIR__ . '/../bootstrap.php';

use Application\Models\Lancamento;
use Application\Models\FaturaCartaoItem;

echo "============================================================\n";
echo "  MIGRAÇÃO: Correção de lançamentos e afeta_caixa\n";
echo "============================================================\n\n";

// ============================================================
// ANÁLISE INICIAL
// ============================================================
echo "--- ANÁLISE INICIAL ---\n\n";

$total = Lancamento::count();
echo "Total de lançamentos: $total\n\n";

$receitasComCartao = Lancamento::where('tipo', 'receita')
    ->whereNotNull('cartao_credito_id')
    ->count();
echo "1. Receitas com cartao_credito_id (ERRO): $receitasComCartao\n";

$transferenciasComCartao = Lancamento::where('tipo', 'transferencia')
    ->whereNotNull('cartao_credito_id')
    ->count();
echo "2. Transferências com cartao_credito_id (ERRO): $transferenciasComCartao\n";

// IDs de lançamentos que têm item de fatura vinculado
$idsComFatura = FaturaCartaoItem::whereNotNull('lancamento_id')
    ->pluck('lancamento_id')
    ->toArray();

$despesasSemFatura = Lancamento::where('tipo', 'despesa')
    ->whereNotNull('cartao_credito_id')
    ->whereNotIn('id', $idsComFatura)
    ->count();
echo "3. Despesas com cartão MAS SEM item de fatura (ERRO): $despesasSemFatura\n";

$afetaTrue = Lancamento::where('afeta_caixa', true)->count();
$afetaFalse = Lancamento::where('afeta_caixa', false)->count();
$afetaNull = Lancamento::whereNull('afeta_caixa')->count();
echo "\nafeta_caixa = true: $afetaTrue\n";
echo "afeta_caixa = false: $afetaFalse\n";
echo "afeta_caixa = NULL: $afetaNull\n";

$totalCorrigir = $receitasComCartao + $transferenciasComCartao + $despesasSemFatura;
echo "\n>> Total de registros a corrigir: $totalCorrigir\n\n";

if ($totalCorrigir === 0 && $afetaNull === 0) {
    echo "✅ Nenhuma correção necessária! Banco já está correto.\n";
    exit(0);
}

// ============================================================
// CONFIRMAÇÃO
// ============================================================
echo "--- CONFIRMAÇÃO ---\n\n";
echo "⚠️  ATENÇÃO: Este script vai modificar os dados do banco!\n";
echo "    Certifique-se de ter um backup antes de continuar.\n\n";
echo "Deseja continuar? (digite 'SIM' para confirmar): ";

$handle = fopen("php://stdin", "r");
$line = trim(fgets($handle));
if ($line !== 'SIM') {
    echo "\n❌ Operação cancelada pelo usuário.\n";
    exit(1);
}

echo "\n--- INICIANDO CORREÇÕES ---\n\n";

// ============================================================
// CORREÇÃO 1: RECEITAS
// ============================================================
$corrigidosReceitas = Lancamento::where('tipo', 'receita')
    ->whereNotNull('cartao_credito_id')
    ->update([
        'cartao_credito_id' => null,
        'afeta_caixa' => true,
        'pago' => true,
    ]);
echo "1. Receitas corrigidas: $corrigidosReceitas\n";

// ============================================================
// CORREÇÃO 2: TRANSFERÊNCIAS
// ============================================================
$corrigidosTransf = Lancamento::where('tipo', 'transferencia')
    ->whereNotNull('cartao_credito_id')
    ->update([
        'cartao_credito_id' => null,
        'afeta_caixa' => true,
    ]);
echo "2. Transferências corrigidas: $corrigidosTransf\n";

// ============================================================
// CORREÇÃO 3: DESPESAS SEM ITEM DE FATURA
// ============================================================
// Recarregar IDs com fatura (pode ter mudado)
$idsComFatura = FaturaCartaoItem::whereNotNull('lancamento_id')
    ->pluck('lancamento_id')
    ->toArray();

$corrigidosDesp = Lancamento::where('tipo', 'despesa')
    ->whereNotNull('cartao_credito_id')
    ->whereNotIn('id', $idsComFatura)
    ->update([
        'cartao_credito_id' => null,
        'afeta_caixa' => true,
        'pago' => true,
    ]);
echo "3. Despesas sem fatura corrigidas: $corrigidosDesp\n";

// ============================================================
// CORREÇÃO 4: LANÇAMENTOS SEM CARTÃO COM afeta_caixa NULL
// ============================================================
$corrigidosSemCartao = Lancamento::whereNull('cartao_credito_id')
    ->where(function ($q) {
        $q->whereNull('afeta_caixa')
            ->orWhere('afeta_caixa', false);
    })
    ->update(['afeta_caixa' => true]);
echo "4. Lançamentos sem cartão com afeta_caixa corrigido: $corrigidosSemCartao\n";

// ============================================================
// CORREÇÃO 5: LANÇAMENTOS DE CARTÃO PAGOS
// ============================================================
$corrigidosCartaoPago = Lancamento::whereNotNull('cartao_credito_id')
    ->where('pago', true)
    ->where(function ($q) {
        $q->whereNull('afeta_caixa')
            ->orWhere('afeta_caixa', false);
    })
    ->update(['afeta_caixa' => true]);
echo "5. Lançamentos de cartão PAGOS corrigidos: $corrigidosCartaoPago\n";

// ============================================================
// CORREÇÃO 6: LANÇAMENTOS DE CARTÃO NÃO PAGOS
// ============================================================
$corrigidosCartaoNaoPago = Lancamento::whereNotNull('cartao_credito_id')
    ->where('pago', false)
    ->where(function ($q) {
        $q->whereNull('afeta_caixa')
            ->orWhere('afeta_caixa', true);
    })
    ->update(['afeta_caixa' => false]);
echo "6. Lançamentos de cartão NÃO PAGOS corrigidos: $corrigidosCartaoNaoPago\n";

// ============================================================
// ESTADO FINAL
// ============================================================
echo "\n--- ESTADO FINAL ---\n\n";

$semCartao = Lancamento::whereNull('cartao_credito_id')->count();
$comCartao = Lancamento::whereNotNull('cartao_credito_id')->count();
echo "Lançamentos SEM cartao_credito_id: $semCartao\n";
echo "Lançamentos COM cartao_credito_id: $comCartao\n\n";

$afetaTrue = Lancamento::where('afeta_caixa', true)->count();
$afetaFalse = Lancamento::where('afeta_caixa', false)->count();
$afetaNull = Lancamento::whereNull('afeta_caixa')->count();
echo "afeta_caixa = true: $afetaTrue (afetam saldo)\n";
echo "afeta_caixa = false: $afetaFalse (não afetam saldo - cartão não pago)\n";
echo "afeta_caixa = NULL: $afetaNull\n";

echo "\n============================================================\n";
echo "  ✅ MIGRAÇÃO CONCLUÍDA COM SUCESSO!\n";
echo "============================================================\n";
