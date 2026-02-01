<?php

/**
 * Script de Diagnóstico de Dados Legados - Cartão de Crédito
 * 
 * Analisa inconsistências causadas pela mudança de lógica:
 * - ANTES: Lançamentos criados no momento do pagamento
 * - AGORA: Lançamentos criados no mês da compra (competência)
 */

require_once __DIR__ . '/../bootstrap.php';

use Application\Models\Lancamento;
use Application\Models\FaturaCartaoItem;
use Application\Models\CartaoCredito;
use Illuminate\Database\Capsule\Manager as DB;

echo "=======================================================================\n";
echo "   DIAGNOSTICO DE DADOS LEGADOS - CARTAO DE CREDITO\n";
echo "=======================================================================\n\n";

// 1. Lançamentos de cartão por mês
echo "1. LANCAMENTOS DE CARTAO POR MES (DATA vs COMPETENCIA)\n";
echo str_repeat("-", 70) . "\n";

$lancamentos = Lancamento::whereNotNull('cartao_credito_id')
    ->selectRaw('
        DATE_FORMAT(data, "%Y-%m") as mes_data,
        DATE_FORMAT(COALESCE(data_competencia, data), "%Y-%m") as mes_competencia,
        COUNT(*) as total,
        SUM(valor) as valor_total,
        SUM(CASE WHEN pago = 1 THEN 1 ELSE 0 END) as pagos,
        SUM(CASE WHEN pago = 0 THEN 1 ELSE 0 END) as pendentes
    ')
    ->groupBy('mes_data', 'mes_competencia')
    ->orderBy('mes_data')
    ->get();

foreach ($lancamentos as $l) {
    $status = $l->mes_data !== $l->mes_competencia ? ' [DIFERENTE]' : '';
    printf(
        "Data: %s | Comp: %s | Total: %3d | R$ %10s | Pagos: %3d | Pend: %3d%s\n",
        $l->mes_data,
        $l->mes_competencia,
        $l->total,
        number_format($l->valor_total, 2, ',', '.'),
        $l->pagos,
        $l->pendentes,
        $status
    );
}

// 2. Itens de Fatura por mês de referência (agrupado por ano-mes)
echo "\n2. ITENS DE FATURA POR MES DE REFERENCIA\n";
echo str_repeat("-", 70) . "\n";

$itensPorMes = FaturaCartaoItem::selectRaw('
        CONCAT(ano_referencia, "-", LPAD(mes_referencia, 2, "0")) as periodo,
        COUNT(*) as total,
        SUM(CASE WHEN pago = 1 THEN 1 ELSE 0 END) as pagos,
        SUM(CASE WHEN pago = 0 THEN 1 ELSE 0 END) as pendentes,
        SUM(valor) as valor_total
    ')
    ->groupBy('periodo')
    ->orderBy('periodo')
    ->get();

foreach ($itensPorMes as $f) {
    printf(
        "Periodo: %s | Total: %3d | Pagos: %3d | Pend: %3d | R$ %s\n",
        $f->periodo,
        $f->total,
        $f->pagos,
        $f->pendentes,
        number_format($f->valor_total, 2, ',', '.')
    );
}

// 3. Comparativo: Itens de Fatura vs Lancamentos vinculados
echo "\n3. ITENS DE FATURA vs LANCAMENTOS VINCULADOS\n";
echo str_repeat("-", 70) . "\n";

$itensAnalise = FaturaCartaoItem::leftJoin('lancamentos', 'faturas_cartao_itens.lancamento_id', '=', 'lancamentos.id')
    ->selectRaw('
        CONCAT(faturas_cartao_itens.ano_referencia, "-", LPAD(faturas_cartao_itens.mes_referencia, 2, "0")) as periodo,
        COUNT(faturas_cartao_itens.id) as total_itens,
        SUM(CASE WHEN faturas_cartao_itens.lancamento_id IS NOT NULL THEN 1 ELSE 0 END) as com_link,
        SUM(CASE WHEN faturas_cartao_itens.lancamento_id IS NULL THEN 1 ELSE 0 END) as sem_link,
        SUM(CASE WHEN lancamentos.pago = 1 THEN 1 ELSE 0 END) as lanc_pagos,
        SUM(CASE WHEN lancamentos.pago = 0 THEN 1 ELSE 0 END) as lanc_pendentes,
        SUM(faturas_cartao_itens.valor) as valor_total
    ')
    ->groupBy('periodo')
    ->orderBy('periodo')
    ->get();

foreach ($itensAnalise as $i) {
    printf(
        "Periodo %s | Itens: %3d | Link: %3d | SemLink: %2d | Pagos: %3d | Pend: %3d | R$ %s\n",
        $i->periodo,
        $i->total_itens,
        $i->com_link,
        $i->sem_link,
        $i->lanc_pagos ?? 0,
        $i->lanc_pendentes ?? 0,
        number_format($i->valor_total, 2, ',', '.')
    );
}

// 4. Detectar possíveis duplicatas
echo "\n4. POSSIVEIS LANCAMENTOS DUPLICADOS (mesma descricao, valor, cartao)\n";
echo str_repeat("-", 70) . "\n";

$duplicatas = Lancamento::whereNotNull('cartao_credito_id')
    ->selectRaw('
        descricao, valor, cartao_credito_id,
        COUNT(*) as quantidade,
        GROUP_CONCAT(id ORDER BY id) as ids,
        GROUP_CONCAT(DATE_FORMAT(data, "%Y-%m-%d") ORDER BY id) as datas,
        GROUP_CONCAT(pago ORDER BY id) as status_pago
    ')
    ->groupBy('descricao', 'valor', 'cartao_credito_id')
    ->havingRaw('COUNT(*) > 1')
    ->orderByRaw('COUNT(*) DESC')
    ->limit(20)
    ->get();

if ($duplicatas->isEmpty()) {
    echo "Nenhuma duplicata encontrada.\n";
} else {
    echo "Encontradas " . $duplicatas->count() . " possiveis duplicatas:\n\n";
    foreach ($duplicatas as $d) {
        printf(
            "  Desc: %-40s | Valor: R$ %8s | Qtd: %d\n",
            mb_substr($d->descricao, 0, 40),
            number_format($d->valor, 2, ',', '.'),
            $d->quantidade
        );
        printf("    IDs: %s\n", $d->ids);
        printf("    Datas: %s\n", $d->datas);
        printf("    Pago: %s\n\n", $d->status_pago);
    }
}

// 5. Lançamentos sem vínculo com item de fatura
echo "\n5. LANCAMENTOS DE CARTAO SEM VINCULO COM ITEM DE FATURA\n";
echo str_repeat("-", 70) . "\n";

$semVinculo = Lancamento::whereNotNull('cartao_credito_id')
    ->whereNotExists(function ($query) {
        $query->select(DB::raw(1))
            ->from('faturas_cartao_itens')
            ->whereRaw('faturas_cartao_itens.lancamento_id = lancamentos.id');
    })
    ->selectRaw('
        DATE_FORMAT(data, "%Y-%m") as mes,
        COUNT(*) as total,
        SUM(valor) as valor_total,
        SUM(CASE WHEN pago = 1 THEN 1 ELSE 0 END) as pagos
    ')
    ->groupBy('mes')
    ->orderBy('mes')
    ->get();

if ($semVinculo->isEmpty()) {
    echo "Todos os lancamentos de cartao estao vinculados a itens de fatura.\n";
} else {
    foreach ($semVinculo as $s) {
        printf(
            "Mes: %s | Sem vinculo: %3d | R$ %10s | Pagos: %3d\n",
            $s->mes,
            $s->total,
            number_format($s->valor_total, 2, ',', '.'),
            $s->pagos
        );
    }
}

// 6. Itens de fatura sem lançamento correspondente
echo "\n6. ITENS DE FATURA SEM LANCAMENTO (dados legados)\n";
echo str_repeat("-", 70) . "\n";

$itensSemLanc = FaturaCartaoItem::whereNull('lancamento_id')
    ->with(['cartaoCredito'])
    ->get();

if ($itensSemLanc->isEmpty()) {
    echo "Todos os itens de fatura tem lancamento vinculado.\n";
} else {
    echo "Encontrados " . $itensSemLanc->count() . " itens sem lancamento:\n";
    foreach ($itensSemLanc as $item) {
        $cartao = $item->cartaoCredito;
        $periodo = $item->ano_referencia . '-' . str_pad($item->mes_referencia, 2, '0', STR_PAD_LEFT);
        printf(
            "  Item #%d: %-35s | R$ %8s | Periodo: %s | Cartao: %s\n",
            $item->id,
            mb_substr($item->descricao, 0, 35),
            number_format($item->valor, 2, ',', '.'),
            $periodo,
            $cartao ? $cartao->nome : 'N/A'
        );
    }
}

// 7. Análise de competência vs data de pagamento
echo "\n7. ANALISE DE COMPETENCIA vs DATA (lancamentos pagos)\n";
echo str_repeat("-", 70) . "\n";

$competenciaErrada = Lancamento::whereNotNull('cartao_credito_id')
    ->where('pago', 1)
    ->whereRaw('DATE_FORMAT(data, "%Y-%m") != DATE_FORMAT(COALESCE(data_competencia, data), "%Y-%m")')
    ->selectRaw('
        id, descricao, valor,
        DATE_FORMAT(data, "%Y-%m-%d") as data_lanc,
        DATE_FORMAT(data_competencia, "%Y-%m-%d") as data_comp,
        afeta_caixa, origem_tipo
    ')
    ->limit(20)
    ->get();

if ($competenciaErrada->isEmpty()) {
    echo "Nenhum lancamento pago com competencia diferente da data.\n";
} else {
    echo "Lancamentos pagos com data != competencia:\n";
    foreach ($competenciaErrada as $l) {
        printf(
            "  #%d: %-30s | R$ %8s | Data: %s | Comp: %s | Caixa: %s\n",
            $l->id,
            mb_substr($l->descricao, 0, 30),
            number_format($l->valor, 2, ',', '.'),
            $l->data_lanc,
            $l->data_comp ?? 'NULL',
            $l->afeta_caixa ? 'SIM' : 'NAO'
        );
    }
}

// 8. Resumo por cartão
echo "\n8. RESUMO POR CARTAO\n";
echo str_repeat("-", 70) . "\n";

$porCartao = CartaoCredito::leftJoin('lancamentos', function ($join) {
    $join->on('cartoes_credito.id', '=', 'lancamentos.cartao_credito_id');
})
    ->selectRaw('
        cartoes_credito.id,
        cartoes_credito.nome,
        COUNT(lancamentos.id) as total_lanc,
        SUM(CASE WHEN lancamentos.pago = 1 THEN 1 ELSE 0 END) as pagos,
        SUM(CASE WHEN lancamentos.pago = 0 THEN 1 ELSE 0 END) as pendentes,
        SUM(CASE WHEN lancamentos.pago = 0 THEN lancamentos.valor ELSE 0 END) as valor_pendente
    ')
    ->groupBy('cartoes_credito.id', 'cartoes_credito.nome')
    ->get();

foreach ($porCartao as $c) {
    printf(
        "Cartao: %-20s | Lanc: %3d | Pagos: %3d | Pend: %3d | Valor Pend: R$ %s\n",
        $c->nome,
        $c->total_lanc,
        $c->pagos,
        $c->pendentes,
        number_format($c->valor_pendente, 2, ',', '.')
    );
}

echo "\n=======================================================================\n";
echo "   FIM DO DIAGNOSTICO\n";
echo "=======================================================================\n";
