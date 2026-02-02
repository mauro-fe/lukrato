<?php

/**
 * Script para corrigir lançamentos com cartao_credito_id errado
 * 
 * Correções:
 * 1. Remover cartao_credito_id de todas as RECEITAS (receitas não são de cartão)
 * 2. Definir afeta_caixa = true para esses lançamentos
 */
require __DIR__ . '/../bootstrap.php';

use Application\Models\Lancamento;

echo "=== Correção de lançamentos com cartão errado ===\n\n";

// Verificar estado atual
$receitasComCartao = Lancamento::where('tipo', 'receita')
    ->whereNotNull('cartao_credito_id')
    ->count();

echo "Estado atual:\n";
echo "  Receitas com cartao_credito_id: $receitasComCartao\n\n";

if ($receitasComCartao === 0) {
    echo "✅ Nenhuma receita com cartão para corrigir!\n";
    exit;
}

// Perguntar confirmação
echo "Deseja corrigir os dados? (s/n): ";
$handle = fopen("php://stdin", "r");
$line = fgets($handle);
if (trim($line) !== 's') {
    echo "Operação cancelada.\n";
    exit;
}

echo "\n--- Iniciando correção ---\n\n";

// Corrigir receitas: remover cartao_credito_id e definir afeta_caixa = true
$corrigidos = Lancamento::where('tipo', 'receita')
    ->whereNotNull('cartao_credito_id')
    ->update([
        'cartao_credito_id' => null,
        'afeta_caixa' => true,
        'pago' => true  // Receitas são consideradas recebidas
    ]);

echo "Receitas corrigidas: $corrigidos\n";

// Verificar estado final
echo "\n--- Estado final ---\n";
$receitasComCartao = Lancamento::where('tipo', 'receita')
    ->whereNotNull('cartao_credito_id')
    ->count();
echo "  Receitas com cartao_credito_id: $receitasComCartao\n";

echo "\n✅ Correção concluída!\n";
