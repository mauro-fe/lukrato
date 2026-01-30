<?php

/**
 * Script para corrigir data_competencia dos lançamentos parcelados
 * 
 * LÓGICA CORRETA:
 * - data = data de vencimento da parcela (fluxo de caixa)
 * - data_competencia = data da COMPRA original (quando a despesa aconteceu)
 * 
 * Exemplo: Compra parcelada em 2x feita em 29/01/2026
 * - Parcela 1/2 → data = 06/02/2026, data_competencia = 29/01/2026
 * - Parcela 2/2 → data = 06/03/2026, data_competencia = 29/01/2026
 * 
 * Assim na visão COMPETÊNCIA: toda a despesa aparece em janeiro
 * Na visão CAIXA: cada parcela aparece no seu mês de vencimento
 */

require_once __DIR__ . '/../bootstrap.php';

use Application\Models\Lancamento;
use Application\Models\FaturaCartaoItem;
use Illuminate\Database\Capsule\Manager as DB;

echo "=== CORREÇÃO DE data_competencia EM LANÇAMENTOS PARCELADOS ===\n\n";

$dryRun = in_array('--dry-run', $argv ?? []);
if ($dryRun) {
    echo "⚠️  MODO DRY-RUN - Nenhuma alteração será feita\n\n";
}

// Buscar todos os lançamentos de cartão parcelados
$lancamentosParcelados = Lancamento::whereNotNull('cartao_credito_id')
    ->where('descricao', 'REGEXP', '\\([0-9]+/[0-9]+\\)$')
    ->whereNotNull('origem_tipo')
    ->get();

echo "Encontrados: {$lancamentosParcelados->count()} lançamentos parcelados\n\n";

$corrigidos = 0;
$jaCorretos = 0;
$erros = 0;

foreach ($lancamentosParcelados as $lancamento) {
    // Buscar o item de fatura vinculado para pegar a data de COMPRA
    $item = FaturaCartaoItem::where('lancamento_id', $lancamento->id)->first();

    if (!$item) {
        echo "⚠️  Item de fatura não encontrado para lançamento {$lancamento->id}\n";
        $erros++;
        continue;
    }

    // A data_competencia deve ser a data da COMPRA
    $dataCompraCorreta = $item->data_compra;
    if ($dataCompraCorreta instanceof \DateTime || $dataCompraCorreta instanceof \Carbon\Carbon) {
        $dataCompraCorreta = $dataCompraCorreta->format('Y-m-d');
    }

    $dataCompetenciaAtual = $lancamento->data_competencia;
    if ($dataCompetenciaAtual instanceof \DateTime || $dataCompetenciaAtual instanceof \Carbon\Carbon) {
        $dataCompetenciaAtual = $dataCompetenciaAtual->format('Y-m-d');
    }

    // Verificar se precisa corrigir
    if ($dataCompetenciaAtual !== $dataCompraCorreta) {
        echo "🔧 Lançamento {$lancamento->id}: {$lancamento->descricao}\n";
        echo "   data_competencia atual: {$dataCompetenciaAtual} → Deveria ser: {$dataCompraCorreta} (data da compra)\n";

        if (!$dryRun) {
            $lancamento->data_competencia = $dataCompraCorreta;
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

echo "\n=== VERIFICAÇÃO FINAL ===\n";
// Mostrar exemplo de como ficou
$exemplo = Lancamento::whereNotNull('cartao_credito_id')
    ->where('descricao', 'LIKE', '%teste3%')
    ->orderBy('id')
    ->get();

if ($exemplo->count() > 0) {
    echo "Exemplo - teste3:\n";
    foreach ($exemplo as $l) {
        $data = $l->data instanceof \Carbon\Carbon ? $l->data->format('Y-m-d') : $l->data;
        $comp = $l->data_competencia instanceof \Carbon\Carbon ? $l->data_competencia->format('Y-m-d') : $l->data_competencia;
        echo "  {$l->descricao}: data={$data} (vencimento), data_competencia={$comp} (compra)\n";
    }
}
