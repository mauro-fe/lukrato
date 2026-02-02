<?php

/**
 * ============================================================================
 * SCRIPT DE CORREÇÃO PARA PRODUÇÃO - COMPETÊNCIA DE FATURAS
 * ============================================================================
 * 
 * Este script corrige o mes_referencia e ano_referencia dos itens de fatura
 * para seguir a regra: competência = mês do vencimento - 1
 * 
 * Exemplo: Compra em janeiro (competência 01) → Vence em fevereiro
 * 
 * EXECUTAR EM PRODUÇÃO: php cli/fix_competencia_producao.php
 */

require __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

echo "============================================================\n";
echo "  CORREÇÃO: Competência de Itens de Fatura\n";
echo "============================================================\n\n";

echo "Regra: mes_referencia = mês do vencimento - 1\n";
echo "(Competência é o mês ANTERIOR ao vencimento)\n\n";

// ============================================================
// PASSO 1: Análise inicial
// ============================================================
echo "--- PASSO 1: Análise inicial ---\n\n";

$totalItens = DB::table('faturas_cartao_itens')->count();
echo "Total de itens na tabela: $totalItens\n\n";

// Contar quantos estão incorretos
$incorretos = DB::select("
    SELECT COUNT(*) as total
    FROM faturas_cartao_itens
    WHERE mes_referencia = MONTH(data_vencimento)
    AND ano_referencia = YEAR(data_vencimento)
");

$qtdIncorretos = $incorretos[0]->total ?? 0;
echo "Itens com competência incorreta (igual ao vencimento): $qtdIncorretos\n\n";

if ($qtdIncorretos === 0) {
    echo "✅ Todos os itens já estão com a competência correta!\n";
    echo "============================================================\n";
    exit(0);
}

// ============================================================
// PASSO 2: Aplicar correção
// ============================================================
echo "--- PASSO 2: Aplicando correção ---\n\n";

$itens = DB::table('faturas_cartao_itens')
    ->select('id', 'descricao', 'data_vencimento', 'mes_referencia', 'ano_referencia')
    ->get();

$corrigidos = 0;
$jaCorretos = 0;
$erros = 0;

foreach ($itens as $item) {
    $mesVenc = (int) date('n', strtotime($item->data_vencimento));
    $anoVenc = (int) date('Y', strtotime($item->data_vencimento));
    
    // Calcular competência correta (1 mês antes do vencimento)
    $mesRefCorreto = $mesVenc - 1;
    $anoRefCorreto = $anoVenc;
    
    if ($mesRefCorreto < 1) {
        $mesRefCorreto = 12;
        $anoRefCorreto--;
    }
    
    // Verificar se já está correto
    if ((int)$item->mes_referencia === $mesRefCorreto && (int)$item->ano_referencia === $anoRefCorreto) {
        $jaCorretos++;
        continue;
    }
    
    // Aplicar correção
    try {
        DB::table('faturas_cartao_itens')
            ->where('id', $item->id)
            ->update([
                'mes_referencia' => $mesRefCorreto,
                'ano_referencia' => $anoRefCorreto,
            ]);
        
        echo "✅ Item {$item->id}: {$item->mes_referencia}/{$item->ano_referencia} → {$mesRefCorreto}/{$anoRefCorreto}\n";
        $corrigidos++;
    } catch (Exception $e) {
        echo "❌ Erro no item {$item->id}: {$e->getMessage()}\n";
        $erros++;
    }
}

// ============================================================
// RESUMO FINAL
// ============================================================
echo "\n============================================================\n";
echo "  RESUMO DA CORREÇÃO\n";
echo "============================================================\n\n";
echo "✅ Corrigidos: $corrigidos\n";
echo "✓ Já estavam corretos: $jaCorretos\n";
echo "❌ Erros: $erros\n";
echo "Total processados: " . ($corrigidos + $jaCorretos + $erros) . "\n\n";

if ($erros === 0) {
    echo "✅ CORREÇÃO CONCLUÍDA COM SUCESSO!\n";
} else {
    echo "⚠️ CORREÇÃO CONCLUÍDA COM ERROS!\n";
}

echo "============================================================\n";
