<?php

/**
 * Script para popular lancamento_id nos itens de fatura legados
 * 
 * Este script encontra FaturaCartaoItem sem lancamento_id e tenta
 * fazer o link com o lançamento correspondente usando a lógica
 * de fallback (busca por descrição e valor).
 * 
 * Uso: php cli/fix_fatura_item_links.php [--dry-run]
 */

require_once __DIR__ . '/../bootstrap.php';

use Application\Models\FaturaCartaoItem;
use Application\Models\Lancamento;

$dryRun = in_array('--dry-run', $argv);

echo "========================================\n";
echo " FIX: Popular lancamento_id em itens legados\n";
echo "========================================\n\n";

if ($dryRun) {
    echo "🔍 MODO DRY-RUN: Nenhuma alteração será feita\n\n";
}

// Buscar itens sem lancamento_id
$itensOrfaos = FaturaCartaoItem::whereNull('lancamento_id')
    ->with(['fatura.cartao'])
    ->get();

$total = $itensOrfaos->count();
echo "📊 Encontrados {$total} itens sem lancamento_id\n\n";

if ($total === 0) {
    echo "✅ Nenhum item órfão encontrado. Sistema consistente!\n";
    exit(0);
}

$fixed = 0;
$notFound = 0;
$errors = [];

foreach ($itensOrfaos as $item) {
    echo "─────────────────────────────────────\n";
    echo "📝 Item #{$item->id}\n";
    echo "   Descrição: {$item->descricao}\n";
    echo "   Valor: R$ " . number_format($item->valor, 2, ',', '.') . "\n";
    echo "   Parcela: {$item->parcela_atual}/{$item->total_parcelas}\n";

    $fatura = $item->fatura;
    if (!$fatura) {
        echo "   ⚠️ Fatura não encontrada!\n";
        $errors[] = "Item #{$item->id}: Fatura não encontrada";
        continue;
    }

    echo "   Fatura: #{$fatura->id} - {$fatura->mes_referencia}\n";

    $cartao = $fatura->cartao;
    if (!$cartao) {
        echo "   ⚠️ Cartão não encontrado!\n";
        $errors[] = "Item #{$item->id}: Cartão não encontrado";
        continue;
    }

    echo "   Cartão: {$cartao->nome} (ID: {$cartao->id})\n";

    // Buscar lançamento correspondente
    $query = Lancamento::where('cartao_credito_id', $cartao->id)
        ->where('user_id', $cartao->user_id)
        ->where('tipo', 'despesa')
        ->where('valor', $item->valor);

    // Tentar match exato por descrição
    $descricaoPattern = $item->descricao;
    if ($item->total_parcelas > 1) {
        // Remover indicador de parcela para busca
        $descricaoPattern = preg_replace('/\s*\(\d+\/\d+\)\s*$/', '', $item->descricao);
    }

    $lancamento = $query->where(function ($q) use ($descricaoPattern, $item) {
        $q->where('descricao', 'LIKE', "%{$descricaoPattern}%");
        if ($item->total_parcelas > 1) {
            $q->where('descricao', 'LIKE', "%{$item->parcela_atual}/{$item->total_parcelas}%");
        }
    })->first();

    if (!$lancamento) {
        // Tentar busca mais flexível
        $lancamento = Lancamento::where('cartao_credito_id', $cartao->id)
            ->where('user_id', $cartao->user_id)
            ->where('tipo', 'despesa')
            ->where('valor', $item->valor)
            ->where('descricao', 'LIKE', "%{$descricaoPattern}%")
            ->orderBy('created_at', 'desc')
            ->first();
    }

    if ($lancamento) {
        echo "   ✅ Lançamento encontrado: #{$lancamento->id}\n";
        echo "      Descrição: {$lancamento->descricao}\n";
        echo "      Data: {$lancamento->data}\n";
        echo "      Pago: " . ($lancamento->pago ? 'Sim' : 'Não') . "\n";

        if (!$dryRun) {
            $item->lancamento_id = $lancamento->id;
            $item->save();
            echo "   💾 Link salvo!\n";
        } else {
            echo "   🔍 [DRY-RUN] Link seria salvo\n";
        }
        $fixed++;
    } else {
        echo "   ❌ Lançamento NÃO encontrado\n";
        $notFound++;
        $errors[] = "Item #{$item->id}: Lançamento não encontrado para '{$item->descricao}'";
    }
}

echo "\n========================================\n";
echo " RESUMO\n";
echo "========================================\n";
echo "Total de itens órfãos: {$total}\n";
echo "Links " . ($dryRun ? "que seriam criados" : "criados") . ": {$fixed}\n";
echo "Não encontrados: {$notFound}\n";

if (!empty($errors)) {
    echo "\n⚠️ ERROS:\n";
    foreach ($errors as $error) {
        echo "   - {$error}\n";
    }
}

if ($dryRun && $fixed > 0) {
    echo "\n💡 Execute sem --dry-run para aplicar as correções:\n";
    echo "   php cli/fix_fatura_item_links.php\n";
}

echo "\n✅ Script finalizado!\n";
