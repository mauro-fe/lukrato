<?php
require __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

echo "=== ANÁLISE DE FATURAS E COMPETÊNCIA ===\n\n";

// Verificar estrutura da tabela faturas
echo "--- COLUNAS DA TABELA FATURAS ---\n";
$cols = DB::select('DESCRIBE faturas');
foreach ($cols as $c) {
    echo "  {$c->Field} ({$c->Type})\n";
}

// Verificar se faturas tem mes/ano referencia
$hasMesRef = false;
foreach ($cols as $c) {
    if ($c->Field === 'mes_referencia') {
        $hasMesRef = true;
        break;
    }
}

echo "\n--- TODAS AS FATURAS ---\n";
$faturas = DB::table('faturas')
    ->select('faturas.*')
    ->orderBy('cartao_credito_id')
    ->orderBy('data_compra')
    ->get();

foreach ($faturas as $f) {
    $dataCompra = $f->data_compra ?? 'N/A';
    echo "  ID: {$f->id} | Cartão: {$f->cartao_credito_id} | Data Compra: {$dataCompra} | Parcelas: {$f->numero_parcelas} | Desc: {$f->descricao} | Status: {$f->status}\n";
}

echo "\n\n--- ITENS COM MES/ANO REFERENCIA ---\n";
$itens = DB::table('faturas_cartao_itens')
    ->select('id', 'fatura_id', 'descricao', 'data_compra', 'data_vencimento', 'mes_referencia', 'ano_referencia', 'parcela_atual', 'total_parcelas', 'pago')
    ->orderBy('fatura_id')
    ->orderBy('ano_referencia')
    ->orderBy('mes_referencia')
    ->orderBy('parcela_atual')
    ->get();

echo "Total de itens: " . count($itens) . "\n\n";

$currentFatura = null;
foreach ($itens as $item) {
    if ($currentFatura !== $item->fatura_id) {
        $currentFatura = $item->fatura_id;
        echo "\n  [FATURA ID: {$item->fatura_id}]\n";
    }

    $pago = $item->pago ? '✅' : '⏳';
    $parcela = $item->parcela_atual && $item->total_parcelas ? "{$item->parcela_atual}/{$item->total_parcelas}" : '-';
    echo "    Item {$item->id}: {$item->mes_referencia}/{$item->ano_referencia} | Venc: {$item->data_vencimento} | Parcela: {$parcela} | {$pago} | {$item->descricao}\n";
}

echo "\n\n--- ANÁLISE DE PROBLEMAS ---\n";

// Verificar itens onde mes_referencia não bate com o mês de data_vencimento
$problemas = DB::table('faturas_cartao_itens')
    ->whereRaw('MONTH(data_vencimento) != mes_referencia')
    ->orWhereRaw('YEAR(data_vencimento) != ano_referencia')
    ->orderBy('fatura_id')
    ->get();

if (count($problemas) > 0) {
    echo "⚠️ Encontrados " . count($problemas) . " itens com mes/ano_referencia diferente do vencimento:\n";
    foreach ($problemas as $p) {
        $mesVenc = date('m', strtotime($p->data_vencimento));
        $anoVenc = date('Y', strtotime($p->data_vencimento));
        echo "  Item {$p->id}: Ref {$p->mes_referencia}/{$p->ano_referencia} vs Venc {$mesVenc}/{$anoVenc} ({$p->data_vencimento}) | {$p->descricao}\n";
    }
} else {
    echo "✅ Todos os itens têm mes/ano_referencia consistentes com data_vencimento\n";
}

// Verificar parcelas fora de ordem
echo "\n--- PARCELAS PARCELADAS (ordenadas) ---\n";
$parcelados = DB::table('faturas_cartao_itens')
    ->where('eh_parcelado', 1)
    ->orderBy('fatura_id')
    ->orderBy('descricao')
    ->orderBy('parcela_atual')
    ->get();

$currentDesc = null;
$currentFatura = null;
$expectedParcela = 1;
$lastMesRef = null;
$lastAnoRef = null;

foreach ($parcelados as $p) {
    $key = "{$p->fatura_id}_{$p->descricao}";

    if ($currentDesc !== $key) {
        $currentDesc = $key;
        $expectedParcela = 1;
        $lastMesRef = null;
        $lastAnoRef = null;
        echo "\n  [{$p->descricao}] (Fatura {$p->fatura_id})\n";
    }

    $problema = '';

    // Verificar se parcela está na ordem
    if ($p->parcela_atual != $expectedParcela) {
        $problema .= " ⚠️ Esperava parcela {$expectedParcela}";
    }

    // Verificar se mês/ano está progressivo
    if ($lastMesRef !== null) {
        $lastDate = "{$lastAnoRef}-{$lastMesRef}-01";
        $currentDate = "{$p->ano_referencia}-{$p->mes_referencia}-01";

        if (strtotime($currentDate) <= strtotime($lastDate)) {
            $problema .= " ⚠️ Competência não é progressiva!";
        }
    }

    $pago = $p->pago ? '✅' : '⏳';
    echo "    Parcela {$p->parcela_atual}/{$p->total_parcelas}: {$p->mes_referencia}/{$p->ano_referencia} | Venc: {$p->data_vencimento} | {$pago}{$problema}\n";

    $expectedParcela++;
    $lastMesRef = $p->mes_referencia;
    $lastAnoRef = $p->ano_referencia;
}

echo "\n\n--- RESUMO POR MÊS/ANO ---\n";
$resumo = DB::table('faturas_cartao_itens')
    ->select('mes_referencia', 'ano_referencia', DB::raw('COUNT(*) as total'), DB::raw('SUM(valor) as valor_total'))
    ->groupBy('mes_referencia', 'ano_referencia')
    ->orderBy('ano_referencia')
    ->orderBy('mes_referencia')
    ->get();

foreach ($resumo as $r) {
    echo "  {$r->mes_referencia}/{$r->ano_referencia}: {$r->total} itens | R$ " . number_format($r->valor_total, 2, ',', '.') . "\n";
}
