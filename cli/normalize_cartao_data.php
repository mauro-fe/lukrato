<?php

/**
 * Script de normalização para corrigir dados existentes após a refatoração
 * 
 * CORREÇÕES:
 * 1. Lançamentos de cartão PENDENTES (não pagos):
 *    - afeta_caixa deve ser FALSE (não afeta saldo até pagar fatura)
 * 
 * 2. Lançamentos de cartão PAGOS:
 *    - afeta_caixa deve ser TRUE (já afetou o saldo quando pagou a fatura)
 * 
 * 3. Itens de fatura SEM lançamento vinculado:
 *    - Criar lançamento para cada item pendente (para seguir o novo fluxo)
 */

require_once __DIR__ . '/../bootstrap.php';

use Application\Models\Lancamento;
use Application\Models\FaturaCartaoItem;
use Application\Models\CartaoCredito;
use Illuminate\Database\Capsule\Manager as DB;

echo "=== NORMALIZAÇÃO DE DADOS APÓS REFATORAÇÃO ===\n\n";

$dryRun = in_array('--dry-run', $argv ?? []);
if ($dryRun) {
    echo "⚠️  MODO DRY-RUN - Nenhuma alteração será feita\n\n";
}

// 1. Corrigir lançamentos de cartão PENDENTES (afeta_caixa deve ser false)
echo "1. CORRIGINDO LANÇAMENTOS PENDENTES...\n";

$lancamentosPendentes = Lancamento::whereNotNull('cartao_credito_id')
    ->where('pago', false)
    ->where('afeta_caixa', true)  // incorreto - deve ser false
    ->get();

echo "   Encontrados: {$lancamentosPendentes->count()} lançamentos pendentes com afeta_caixa=true (incorreto)\n";

if (!$dryRun && $lancamentosPendentes->count() > 0) {
    foreach ($lancamentosPendentes as $lancamento) {
        $lancamento->afeta_caixa = false;
        $lancamento->save();
    }
    echo "   ✅ Corrigidos {$lancamentosPendentes->count()} lançamentos (afeta_caixa=false)\n";
}

// 2. Verificar lançamentos PAGOS (afeta_caixa deve ser true) - já estão corretos
echo "\n2. VERIFICANDO LANÇAMENTOS PAGOS...\n";

$lancamentosPagos = Lancamento::whereNotNull('cartao_credito_id')
    ->where('pago', true)
    ->where('afeta_caixa', false)  // incorreto - deve ser true
    ->get();

echo "   Encontrados: {$lancamentosPagos->count()} lançamentos pagos com afeta_caixa=false (incorreto)\n";

if (!$dryRun && $lancamentosPagos->count() > 0) {
    foreach ($lancamentosPagos as $lancamento) {
        $lancamento->afeta_caixa = true;
        $lancamento->save();
    }
    echo "   ✅ Corrigidos {$lancamentosPagos->count()} lançamentos (afeta_caixa=true)\n";
}

// 3. Criar lançamentos para itens de fatura PENDENTES sem lançamento vinculado
echo "\n3. CRIANDO LANÇAMENTOS PARA ITENS PENDENTES SEM VÍNCULO...\n";

// Apenas itens cujo usuário ainda existe
$itensSemLancamento = FaturaCartaoItem::whereNull('lancamento_id')
    ->where('pago', false)
    ->whereIn('user_id', function ($query) {
        $query->select('id')->from('usuarios');
    })
    ->get();

echo "   Encontrados: {$itensSemLancamento->count()} itens pendentes sem lançamento vinculado (com usuário válido)\n";

// Contar itens órfãos (usuário deletado)
$itensOrfaos = DB::table('faturas_cartao_itens')
    ->whereNull('lancamento_id')
    ->where('pago', false)
    ->whereNotIn('user_id', function ($query) {
        $query->select('id')->from('usuarios');
    })
    ->count();

if ($itensOrfaos > 0) {
    echo "   ⚠️  Ignorando {$itensOrfaos} itens órfãos (usuário deletado)\n";
}

if (!$dryRun && $itensSemLancamento->count() > 0) {
    $criados = 0;
    foreach ($itensSemLancamento as $item) {
        // Buscar cartão
        $cartao = CartaoCredito::find($item->cartao_credito_id);
        if (!$cartao) {
            echo "   ⚠️  Cartão não encontrado para item {$item->id}\n";
            continue;
        }

        $dataCompra = $item->data_compra ? $item->data_compra->format('Y-m-d') : date('Y-m-d');

        $lancamento = Lancamento::create([
            'user_id' => $item->user_id,
            'conta_id' => $cartao->conta_id,
            'categoria_id' => $item->categoria_id,
            'cartao_credito_id' => $item->cartao_credito_id,
            'tipo' => 'despesa',
            'valor' => $item->valor,
            'descricao' => $item->descricao,
            'data' => $dataCompra,
            'data_competencia' => $dataCompra,
            'observacao' => sprintf(
                'Compra cartão %s (migrado - parcela %d/%d)',
                $cartao->nome_cartao ?? $cartao->bandeira ?? 'Cartão',
                $item->parcela_atual ?? 1,
                $item->total_parcelas ?? 1
            ),
            'pago' => false,
            'data_pagamento' => null,
            'afeta_competencia' => true,
            'afeta_caixa' => false,  // PENDENTE - não afeta caixa ainda
            'origem_tipo' => 'cartao_credito',
        ]);

        // Vincular item ao lançamento
        $item->lancamento_id = $lancamento->id;
        $item->save();

        $criados++;
    }
    echo "   ✅ Criados {$criados} lançamentos e vinculados aos itens\n";
}

// 4. Criar lançamentos para itens de fatura PAGOS sem lançamento vinculado
echo "\n4. CRIANDO LANÇAMENTOS PARA ITENS PAGOS SEM VÍNCULO...\n";

// Apenas itens cujo usuário ainda existe
$itensPagosSemLancamento = FaturaCartaoItem::whereNull('lancamento_id')
    ->where('pago', true)
    ->whereIn('user_id', function ($query) {
        $query->select('id')->from('usuarios');
    })
    ->get();

echo "   Encontrados: {$itensPagosSemLancamento->count()} itens pagos sem lançamento vinculado (com usuário válido)\n";

// Contar itens órfãos (usuário deletado)
$itensPagosOrfaos = DB::table('faturas_cartao_itens')
    ->whereNull('lancamento_id')
    ->where('pago', true)
    ->whereNotIn('user_id', function ($query) {
        $query->select('id')->from('usuarios');
    })
    ->count();

if ($itensPagosOrfaos > 0) {
    echo "   ⚠️  Ignorando {$itensPagosOrfaos} itens pagos órfãos (usuário deletado)\n";
}

if (!$dryRun && $itensPagosSemLancamento->count() > 0) {
    $criados = 0;
    foreach ($itensPagosSemLancamento as $item) {
        // Buscar cartão
        $cartao = CartaoCredito::find($item->cartao_credito_id);
        if (!$cartao) {
            echo "   ⚠️  Cartão não encontrado para item {$item->id}\n";
            continue;
        }

        $dataCompra = $item->data_compra ? $item->data_compra->format('Y-m-d') : date('Y-m-d');
        $dataPagamento = $item->data_pagamento ? $item->data_pagamento->format('Y-m-d') : date('Y-m-d');

        $lancamento = Lancamento::create([
            'user_id' => $item->user_id,
            'conta_id' => $cartao->conta_id,
            'categoria_id' => $item->categoria_id,
            'cartao_credito_id' => $item->cartao_credito_id,
            'tipo' => 'despesa',
            'valor' => $item->valor,
            'descricao' => $item->descricao,
            'data' => $dataCompra,
            'data_competencia' => $dataCompra,
            'observacao' => sprintf(
                'Compra cartão %s (migrado - parcela %d/%d) - pago em %s',
                $cartao->nome_cartao ?? $cartao->bandeira ?? 'Cartão',
                $item->parcela_atual ?? 1,
                $item->total_parcelas ?? 1,
                date('d/m/Y', strtotime($dataPagamento))
            ),
            'pago' => true,
            'data_pagamento' => $dataPagamento,
            'afeta_competencia' => true,
            'afeta_caixa' => true,  // PAGO - afeta caixa
            'origem_tipo' => 'cartao_credito',
        ]);

        // Vincular item ao lançamento
        $item->lancamento_id = $lancamento->id;
        $item->save();

        $criados++;
    }
    echo "   ✅ Criados {$criados} lançamentos e vinculados aos itens\n";
}

// 5. Relatório final
echo "\n=== RELATÓRIO FINAL ===\n";

$stats = DB::table('lancamentos')
    ->whereNotNull('cartao_credito_id')
    ->selectRaw("
        COUNT(*) as total,
        SUM(CASE WHEN pago = 1 THEN 1 ELSE 0 END) as pagos,
        SUM(CASE WHEN pago = 0 THEN 1 ELSE 0 END) as pendentes,
        SUM(CASE WHEN pago = 0 AND afeta_caixa = 0 THEN 1 ELSE 0 END) as pendentes_corretos,
        SUM(CASE WHEN pago = 1 AND afeta_caixa = 1 THEN 1 ELSE 0 END) as pagos_corretos
    ")
    ->first();

echo "Lançamentos de cartão: {$stats->total}\n";
echo "  - Pagos: {$stats->pagos} (" . ($stats->pagos == $stats->pagos_corretos ? "✅ todos corretos" : "⚠️ {$stats->pagos_corretos} corretos") . ")\n";
echo "  - Pendentes: {$stats->pendentes} (" . ($stats->pendentes == $stats->pendentes_corretos ? "✅ todos corretos" : "⚠️ {$stats->pendentes_corretos} corretos") . ")\n";

$itensStats = DB::table('faturas_cartao_itens')
    ->selectRaw("
        COUNT(*) as total,
        SUM(CASE WHEN lancamento_id IS NOT NULL THEN 1 ELSE 0 END) as com_lancamento,
        SUM(CASE WHEN lancamento_id IS NULL THEN 1 ELSE 0 END) as sem_lancamento
    ")
    ->first();

echo "\nItens de fatura: {$itensStats->total}\n";
echo "  - Com lançamento vinculado: {$itensStats->com_lancamento}\n";
echo "  - Sem lançamento vinculado: {$itensStats->sem_lancamento}\n";

if ($dryRun) {
    echo "\n⚠️  Execute novamente sem --dry-run para aplicar as correções\n";
} else {
    echo "\n✅ NORMALIZAÇÃO CONCLUÍDA!\n";
}
