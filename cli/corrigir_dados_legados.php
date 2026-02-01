<?php

/**
 * Script de Correção de Dados Legados - Cartão de Crédito
 * 
 * Este script corrige dados criados antes da refatoração do sistema de cartão.
 * 
 * Problemas tratados:
 * 1. Lançamentos de cartão sem vínculo com FaturaCartaoItem (dados antigos)
 * 2. Itens de fatura sem lancamento_id (órfãos)
 * 
 * Uso: 
 *   php cli/corrigir_dados_legados.php --dry-run    (apenas simula)
 *   php cli/corrigir_dados_legados.php --execute    (aplica correções)
 */

require_once __DIR__ . '/../bootstrap.php';

use Application\Models\Lancamento;
use Application\Models\FaturaCartaoItem;
use Illuminate\Database\Capsule\Manager as DB;

$dryRun = in_array('--dry-run', $argv) || !in_array('--execute', $argv);

echo "=======================================================================\n";
echo "   CORRECAO DE DADOS LEGADOS - CARTAO DE CREDITO\n";
echo "=======================================================================\n\n";

if ($dryRun) {
    echo "⚠️  MODO DRY-RUN: Nenhuma alteracao sera feita no banco.\n";
    echo "    Use --execute para aplicar as correcoes.\n\n";
} else {
    echo "🔥 MODO EXECUCAO: As correcoes serao aplicadas!\n\n";
}

$estatisticas = [
    'lancamentos_atualizados' => 0,
    'itens_vinculados' => 0,
    'itens_sem_lancamento' => 0,
    'erros' => [],
];

// =============================================================================
// 1. VINCULAR LANCAMENTOS ANTIGOS A ITENS DE FATURA
// =============================================================================
echo "1. VINCULANDO LANCAMENTOS ANTIGOS A ITENS DE FATURA\n";
echo str_repeat("-", 70) . "\n";

// Buscar itens de fatura sem lancamento_id
$itensSemLink = FaturaCartaoItem::whereNull('lancamento_id')
    ->get();

echo "   Itens de fatura sem lancamento_id: " . $itensSemLink->count() . "\n\n";

foreach ($itensSemLink as $item) {
    // Tentar encontrar lançamento correspondente
    $query = Lancamento::where('cartao_credito_id', $item->cartao_credito_id)
        ->where('user_id', $item->user_id)
        ->where('tipo', 'despesa')
        ->where('valor', $item->valor);

    // Buscar por descrição similar
    $descricaoBase = preg_replace('/\s*\(\d+\/\d+\)\s*$/', '', $item->descricao);

    if ($item->total_parcelas > 1 && $item->parcela_atual > 0) {
        // Para parcelas, buscar descrição com indicador de parcela
        $lancamento = $query->where('descricao', 'LIKE', "%{$descricaoBase}%")
            ->where('descricao', 'LIKE', "%({$item->parcela_atual}/{$item->total_parcelas})%")
            ->first();
    } else {
        // Para compras à vista
        $lancamento = $query->where('descricao', 'LIKE', "%{$descricaoBase}%")
            ->first();
    }

    if ($lancamento) {
        echo "   ✅ Item #{$item->id} -> Lancamento #{$lancamento->id}\n";
        echo "      Desc: {$item->descricao}\n";

        if (!$dryRun) {
            $item->lancamento_id = $lancamento->id;
            $item->save();
        }
        $estatisticas['itens_vinculados']++;
    } else {
        echo "   ❌ Item #{$item->id} sem lancamento correspondente\n";
        echo "      Desc: {$item->descricao} | Valor: R$ " . number_format($item->valor, 2, ',', '.') . "\n";
        $estatisticas['itens_sem_lancamento']++;
    }
}

// =============================================================================
// 2. CRIAR ITENS DE FATURA PARA LANCAMENTOS ANTIGOS SEM VINCULO
// =============================================================================
echo "\n2. ANALISE DE LANCAMENTOS SEM VINCULO COM FATURA\n";
echo str_repeat("-", 70) . "\n";

// Lançamentos de cartão sem vínculo com item de fatura
$lancamentosSemVinculo = Lancamento::whereNotNull('cartao_credito_id')
    ->whereNotExists(function ($query) {
        $query->select(DB::raw(1))
            ->from('faturas_cartao_itens')
            ->whereRaw('faturas_cartao_itens.lancamento_id = lancamentos.id');
    })
    ->selectRaw('
        DATE_FORMAT(data, "%Y-%m") as mes,
        COUNT(*) as total,
        SUM(valor) as valor_total
    ')
    ->groupBy('mes')
    ->orderBy('mes')
    ->get();

echo "   Lancamentos de cartao sem item de fatura vinculado:\n\n";

$totalSemVinculo = 0;
foreach ($lancamentosSemVinculo as $l) {
    echo "   Mes: {$l->mes} | Total: {$l->total} | R$ " . number_format($l->valor_total, 2, ',', '.') . "\n";
    $totalSemVinculo += $l->total;
}

echo "\n   TOTAL: {$totalSemVinculo} lancamentos\n\n";

if ($totalSemVinculo > 0) {
    echo "   ⚠️  ATENCAO: Estes lancamentos sao do modelo ANTIGO.\n";
    echo "       Antes da refatoracao, lancamentos eram criados sem FaturaCartaoItem.\n";
    echo "       Recomendacao: Manter como estao (funcionam normalmente).\n";
    echo "       Apenas novos lancamentos usam o sistema de faturas.\n";
}

// =============================================================================
// 3. VERIFICAR CONSISTENCIA DE AFETA_CAIXA
// =============================================================================
echo "\n3. VERIFICANDO CONSISTENCIA DO CAMPO afeta_caixa\n";
echo str_repeat("-", 70) . "\n";

// Lançamentos pendentes que afetam caixa (ERRADO)
$pendentesComCaixa = Lancamento::whereNotNull('cartao_credito_id')
    ->where('pago', 0)
    ->where('afeta_caixa', 1)
    ->count();

if ($pendentesComCaixa > 0) {
    echo "   ❌ PROBLEMA: {$pendentesComCaixa} lancamentos pendentes com afeta_caixa=true\n";

    if (!$dryRun) {
        $updated = Lancamento::whereNotNull('cartao_credito_id')
            ->where('pago', 0)
            ->where('afeta_caixa', 1)
            ->update(['afeta_caixa' => false]);
        echo "   ✅ Corrigidos: {$updated} lancamentos\n";
        $estatisticas['lancamentos_atualizados'] += $updated;
    } else {
        echo "   -> Sera corrigido no modo --execute\n";
    }
} else {
    echo "   ✅ Todos os lancamentos pendentes tem afeta_caixa=false\n";
}

// Lançamentos pagos que NÃO afetam caixa (pode ser ERRADO)
$pagossSemCaixa = Lancamento::whereNotNull('cartao_credito_id')
    ->where('pago', 1)
    ->where('afeta_caixa', 0)
    ->count();

if ($pagossSemCaixa > 0) {
    echo "   ❌ PROBLEMA: {$pagossSemCaixa} lancamentos PAGOS com afeta_caixa=false\n";

    if (!$dryRun) {
        $updated = Lancamento::whereNotNull('cartao_credito_id')
            ->where('pago', 1)
            ->where('afeta_caixa', 0)
            ->update(['afeta_caixa' => true]);
        echo "   ✅ Corrigidos: {$updated} lancamentos\n";
        $estatisticas['lancamentos_atualizados'] += $updated;
    } else {
        echo "   -> Sera corrigido no modo --execute\n";
    }
} else {
    echo "   ✅ Todos os lancamentos pagos tem afeta_caixa=true\n";
}

// =============================================================================
// 4. VERIFICAR DATA_COMPETENCIA
// =============================================================================
echo "\n4. VERIFICANDO CAMPO data_competencia\n";
echo str_repeat("-", 70) . "\n";

// Lançamentos de cartão sem data_competencia
$semCompetencia = Lancamento::whereNotNull('cartao_credito_id')
    ->whereNull('data_competencia')
    ->count();

if ($semCompetencia > 0) {
    echo "   ⚠️  {$semCompetencia} lancamentos sem data_competencia\n";
    echo "   -> Usando 'data' como fallback (COALESCE funciona)\n";

    // Opcional: popular data_competencia com data para dados antigos
    if (!$dryRun) {
        $updated = Lancamento::whereNotNull('cartao_credito_id')
            ->whereNull('data_competencia')
            ->update(['data_competencia' => DB::raw('data')]);
        echo "   ✅ Populado data_competencia para {$updated} lancamentos\n";
        $estatisticas['lancamentos_atualizados'] += $updated;
    }
} else {
    echo "   ✅ Todos os lancamentos de cartao tem data_competencia\n";
}

// =============================================================================
// 5. VERIFICAR ORIGEM_TIPO
// =============================================================================
echo "\n5. VERIFICANDO CAMPO origem_tipo\n";
echo str_repeat("-", 70) . "\n";

// Lançamentos de cartão sem origem_tipo correto
$semOrigem = Lancamento::whereNotNull('cartao_credito_id')
    ->where(function ($q) {
        $q->whereNull('origem_tipo')
            ->orWhere('origem_tipo', 'normal');
    })
    ->count();

if ($semOrigem > 0) {
    echo "   ⚠️  {$semOrigem} lancamentos de cartao sem origem_tipo='cartao_credito'\n";

    if (!$dryRun) {
        $updated = Lancamento::whereNotNull('cartao_credito_id')
            ->where(function ($q) {
                $q->whereNull('origem_tipo')
                    ->orWhere('origem_tipo', 'normal');
            })
            ->update(['origem_tipo' => 'cartao_credito']);
        echo "   ✅ Corrigidos: {$updated} lancamentos\n";
        $estatisticas['lancamentos_atualizados'] += $updated;
    } else {
        echo "   -> Sera corrigido no modo --execute\n";
    }
} else {
    echo "   ✅ Todos os lancamentos de cartao tem origem_tipo='cartao_credito'\n";
}

// =============================================================================
// RESUMO
// =============================================================================
echo "\n=======================================================================\n";
echo "   RESUMO\n";
echo "=======================================================================\n";

if ($dryRun) {
    echo "   MODO DRY-RUN - Nenhuma alteracao foi aplicada.\n\n";
} else {
    echo "   CORRECOES APLICADAS:\n\n";
}

echo "   Lancamentos atualizados: {$estatisticas['lancamentos_atualizados']}\n";
echo "   Itens de fatura vinculados: {$estatisticas['itens_vinculados']}\n";
echo "   Itens sem lancamento encontrado: {$estatisticas['itens_sem_lancamento']}\n";

if (!empty($estatisticas['erros'])) {
    echo "\n   ERROS:\n";
    foreach ($estatisticas['erros'] as $erro) {
        echo "   - {$erro}\n";
    }
}

if ($dryRun) {
    echo "\n   💡 Execute com --execute para aplicar as correcoes:\n";
    echo "      php cli/corrigir_dados_legados.php --execute\n";
}

echo "\n=======================================================================\n";
