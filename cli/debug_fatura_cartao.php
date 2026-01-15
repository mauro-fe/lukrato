#!/usr/bin/env php
<?php
/**
 * Debug detalhado de faturas de um cartão
 */

require_once dirname(__DIR__) . '/bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

$cartaoId = $argv[1] ?? 35;

echo "=== Detalhes do Cartão ID: {$cartaoId} ===" . PHP_EOL . PHP_EOL;

$cartao = DB::table('cartoes_credito')->find($cartaoId);
if ($cartao) {
    echo "Cartão: {$cartao->nome_cartao}" . PHP_EOL;
    echo "User ID: {$cartao->user_id}" . PHP_EOL;
    echo "Limite: R$ " . number_format($cartao->limite_total, 2, ',', '.') . PHP_EOL;
    echo PHP_EOL;
}

echo "=== Todas as Faturas (tabela 'faturas') ===" . PHP_EOL;
$faturas = DB::table('faturas')
    ->where('cartao_credito_id', $cartaoId)
    ->orderBy('id')
    ->get();

foreach ($faturas as $f) {
    echo "  ID: {$f->id} | {$f->descricao} | Status: {$f->status} | Valor: R$ " . number_format($f->valor_total, 2, ',', '.') . PHP_EOL;
}

echo PHP_EOL . "=== Todos os Itens de Fatura (tabela 'faturas_cartao_itens') ===" . PHP_EOL;
$itens = DB::table('faturas_cartao_itens')
    ->where('cartao_credito_id', $cartaoId)
    ->orderBy('data_vencimento')
    ->get();

echo "  Total de itens: " . count($itens) . PHP_EOL . PHP_EOL;

foreach ($itens as $i) {
    $pago = $i->pago ? '✅ PAGO' : '⏳ Pendente';
    $mesRef = $i->mes_referencia ? "{$i->mes_referencia}/{$i->ano_referencia}" : "N/A";
    echo "  ID: {$i->id} | Fatura: {$i->fatura_id} | MesRef: {$mesRef} | Venc: {$i->data_vencimento} | {$pago} | R$ " . number_format($i->valor, 2, ',', '.') . " | {$i->descricao}" . PHP_EOL;
}

echo PHP_EOL . "=== Resumo por Mês de Referência ===" . PHP_EOL;
$porMes = DB::table('faturas_cartao_itens')
    ->where('cartao_credito_id', $cartaoId)
    ->selectRaw('mes_referencia, ano_referencia, pago, COUNT(*) as qtd, SUM(valor) as total')
    ->groupBy('mes_referencia', 'ano_referencia', 'pago')
    ->orderBy('ano_referencia')
    ->orderBy('mes_referencia')
    ->get();

foreach ($porMes as $m) {
    $status = $m->pago ? 'PAGOS' : 'PENDENTES';
    $mes = $m->mes_referencia ? "{$m->mes_referencia}/{$m->ano_referencia}" : "Sem ref";
    echo "  {$mes}: {$m->qtd} {$status} - R$ " . number_format($m->total, 2, ',', '.') . PHP_EOL;
}
