<?php

/**
 * ============================================================================
 * SCRIPT DE MIGRAÇÃO PARA PRODUÇÃO - FORMA DE PAGAMENTO
 * ============================================================================
 * 
 * Este script adiciona a coluna forma_pagamento e atualiza os lançamentos
 * existentes com cartão de crédito.
 * 
 * EXECUTAR EM PRODUÇÃO: php cli/migrate_forma_pagamento_producao.php
 */

require __DIR__ . '/../bootstrap.php';

use Application\Models\Lancamento;
use Illuminate\Database\Capsule\Manager as DB;

echo "============================================================\n";
echo "  MIGRAÇÃO: Adicionar forma_pagamento\n";
echo "============================================================\n\n";

// ============================================================
// PASSO 1: Adicionar coluna forma_pagamento (se não existir)
// ============================================================
echo "--- PASSO 1: Verificar coluna forma_pagamento ---\n\n";

$columns = DB::select("SHOW COLUMNS FROM lancamentos LIKE 'forma_pagamento'");

if (empty($columns)) {
    echo "Adicionando coluna 'forma_pagamento'...\n";

    DB::statement("
        ALTER TABLE lancamentos 
        ADD COLUMN forma_pagamento VARCHAR(30) NULL DEFAULT NULL 
        COMMENT 'Forma de pagamento/recebimento: pix, cartao_credito, cartao_debito, dinheiro, boleto, deposito, transferencia, estorno_cartao'
        AFTER cartao_credito_id
    ");

    echo "✅ Coluna 'forma_pagamento' adicionada com sucesso!\n\n";
} else {
    echo "ℹ️ Coluna 'forma_pagamento' já existe.\n\n";
}

// ============================================================
// PASSO 2: Atualizar lançamentos com cartão de crédito
// ============================================================
echo "--- PASSO 2: Atualizar despesas com cartão ---\n\n";

$updated = DB::table('lancamentos')
    ->where('tipo', 'despesa')
    ->whereNotNull('cartao_credito_id')
    ->whereNull('forma_pagamento')
    ->update(['forma_pagamento' => 'cartao_credito']);

if ($updated > 0) {
    echo "✅ $updated despesas com cartão atualizadas para 'cartao_credito'\n\n";
} else {
    echo "ℹ️ Nenhuma despesa precisou ser atualizada.\n\n";
}

// ============================================================
// ESTADO FINAL
// ============================================================
echo "--- ESTADO FINAL ---\n\n";

$total = Lancamento::count();
$comFormaPag = Lancamento::whereNotNull('forma_pagamento')->count();
$semFormaPag = Lancamento::whereNull('forma_pagamento')->count();

echo "Total de lançamentos: $total\n";
echo "Com forma_pagamento definida: $comFormaPag\n";
echo "Sem forma_pagamento (null): $semFormaPag\n";

// Detalhamento por forma de pagamento
$porForma = DB::table('lancamentos')
    ->select('forma_pagamento', DB::raw('COUNT(*) as total'))
    ->whereNotNull('forma_pagamento')
    ->groupBy('forma_pagamento')
    ->get();

if (count($porForma) > 0) {
    echo "\nDistribuição por forma de pagamento:\n";
    foreach ($porForma as $item) {
        echo "  - {$item->forma_pagamento}: {$item->total}\n";
    }
}

echo "\n============================================================\n";
echo "  ✅ MIGRAÇÃO CONCLUÍDA COM SUCESSO!\n";
echo "============================================================\n";
