<?php

/**
 * Script para corrigir a data dos lançamentos parcelados de cartão de crédito
 * 
 * PROBLEMA: Todas as parcelas estavam com a mesma data (data da compra)
 * CORREÇÃO: Cada parcela deve ter a data do seu mês de vencimento
 * 
 * Exemplo: Compra parcelada em 2x feita em 29/01/2026
 * - Parcela 1/2 → data = janeiro/2026 (vencimento fatura janeiro)
 * - Parcela 2/2 → data = fevereiro/2026 (vencimento fatura fevereiro)
 */

require_once __DIR__ . '/../bootstrap.php';

use Application\Models\Lancamento;
use Application\Models\FaturaCartaoItem;
use Illuminate\Database\Capsule\Manager as DB;

echo "=== CORREÇÃO DE DATAS EM LANÇAMENTOS PARCELADOS ===\n\n";

$dryRun = in_array('--dry-run', $argv ?? []);
if ($dryRun) {
    echo "⚠️  MODO DRY-RUN - Nenhuma alteração será feita\n\n";
}

// Buscar todos os lançamentos de cartão parcelados (com /2, /3, etc. na descrição)
$lancamentosParcelados = Lancamento::whereNotNull('cartao_credito_id')
    ->where('descricao', 'REGEXP', '\\([0-9]+/[0-9]+\\)$')  // Termina com (X/Y)
    ->whereNotNull('origem_tipo')
    ->get();

echo "Encontrados: {$lancamentosParcelados->count()} lançamentos parcelados\n\n";

$corrigidos = 0;
$jaCorretos = 0;
$erros = 0;

foreach ($lancamentosParcelados as $lancamento) {
    // Extrair número da parcela da descrição: "teste (2/2)" -> 2
    if (!preg_match('/\((\d+)\/(\d+)\)$/', $lancamento->descricao, $matches)) {
        echo "⚠️  Não foi possível extrair parcela de: {$lancamento->descricao}\n";
        $erros++;
        continue;
    }

    $parcelaAtual = (int)$matches[1];
    $totalParcelas = (int)$matches[2];

    // Buscar o item de fatura vinculado para pegar a data de vencimento correta
    $item = FaturaCartaoItem::where('lancamento_id', $lancamento->id)->first();

    if (!$item) {
        echo "⚠️  Item de fatura não encontrado para lançamento {$lancamento->id}\n";
        $erros++;
        continue;
    }

    $dataVencimentoCorreta = $item->data_vencimento;
    if ($dataVencimentoCorreta instanceof \DateTime || $dataVencimentoCorreta instanceof \Carbon\Carbon) {
        $dataVencimentoCorreta = $dataVencimentoCorreta->format('Y-m-d');
    }

    $dataAtual = $lancamento->data;
    if ($dataAtual instanceof \DateTime || $dataAtual instanceof \Carbon\Carbon) {
        $dataAtual = $dataAtual->format('Y-m-d');
    }

    // Verificar se precisa corrigir
    // Para parcela 2 em diante, a data deveria ser do mês de vencimento, não da compra
    $dataCompetenciaAtual = $lancamento->data_competencia;
    if ($dataCompetenciaAtual instanceof \DateTime || $dataCompetenciaAtual instanceof \Carbon\Carbon) {
        $dataCompetenciaAtual = $dataCompetenciaAtual->format('Y-m-d');
    }

    $mesDataAtual = date('m', strtotime($dataAtual));
    $mesVencimento = date('m', strtotime($dataVencimentoCorreta));

    if ($mesDataAtual !== $mesVencimento) {
        echo "🔧 Lançamento {$lancamento->id}: {$lancamento->descricao}\n";
        echo "   Data atual: {$dataAtual} → Deveria ser: {$dataVencimentoCorreta}\n";
        echo "   Competência atual: {$dataCompetenciaAtual} → Deveria ser: {$dataVencimentoCorreta}\n";

        if (!$dryRun) {
            $lancamento->data = $dataVencimentoCorreta;
            $lancamento->data_competencia = $dataVencimentoCorreta;
            $lancamento->save();
            echo "   ✅ Corrigido!\n";
        }

        $corrigidos++;
    } else {
        $jaCorretos++;
    }
}

echo "\n=== RESUMO ===\n";
echo "Total analisados: {$lancamentosParcelados->count()}\n";
echo "Já corretos: {$jaCorretos}\n";
echo "Corrigidos: {$corrigidos}\n";
echo "Erros: {$erros}\n";

if ($dryRun && $corrigidos > 0) {
    echo "\n⚠️  Execute novamente sem --dry-run para aplicar as correções\n";
} elseif ($corrigidos > 0) {
    echo "\n✅ CORREÇÃO CONCLUÍDA!\n";
} else {
    echo "\n✅ Nenhuma correção necessária!\n";
}
