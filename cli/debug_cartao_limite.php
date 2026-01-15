#!/usr/bin/env php
<?php
/**
 * Script para analisar faturas e lançamentos de um cartão
 */

require_once dirname(__DIR__) . '/bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;
use Application\Models\CartaoCredito;
use Application\Models\Lancamento;

$cartaoId = $argv[1] ?? 32;

echo "=== Análise do Cartão ID: {$cartaoId} ===" . PHP_EOL . PHP_EOL;

$cartao = CartaoCredito::find($cartaoId);
if (!$cartao) {
    echo "Cartão não encontrado!" . PHP_EOL;
    exit(1);
}

echo "📌 Cartão: {$cartao->nome_cartao}" . PHP_EOL;
echo "   Limite Total: R$ " . number_format($cartao->limite_total, 2, ',', '.') . PHP_EOL;
echo "   Limite Disponível (atual): R$ " . number_format($cartao->limite_disponivel, 2, ',', '.') . PHP_EOL;
echo "   Dia Fechamento: {$cartao->dia_fechamento}" . PHP_EOL;
echo "   Dia Vencimento: {$cartao->dia_vencimento}" . PHP_EOL;
echo PHP_EOL;

// Verificar faturas
echo "=== Faturas do Cartão ===" . PHP_EOL;
$faturas = DB::table('faturas')
    ->where('cartao_credito_id', $cartaoId)
    ->orderBy('data_compra', 'desc')
    ->limit(10)
    ->get();

if ($faturas->isEmpty()) {
    echo "Nenhuma fatura encontrada na tabela 'faturas'" . PHP_EOL;
} else {
    foreach ($faturas as $f) {
        echo "  Fatura #{$f->id}: {$f->descricao} - Status: {$f->status} - R$ " . number_format($f->valor_total, 2, ',', '.') . PHP_EOL;
    }
}
echo PHP_EOL;

// Resumo de lançamentos por status de pagamento
echo "=== Resumo Lançamentos ===" . PHP_EOL;
$resumo = DB::table('lancamentos')
    ->where('cartao_credito_id', $cartaoId)
    ->selectRaw('pago, COUNT(*) as qtd, SUM(valor) as total')
    ->groupBy('pago')
    ->get();

foreach ($resumo as $r) {
    $status = $r->pago ? 'PAGOS' : 'NÃO PAGOS';
    echo "  {$status}: {$r->qtd} lançamentos - R$ " . number_format($r->total, 2, ',', '.') . PHP_EOL;
}
echo PHP_EOL;

// Lançamentos não pagos por mês de referência
echo "=== Lançamentos NÃO PAGOS por Mês ===" . PHP_EOL;
$porMes = DB::table('lancamentos')
    ->where('cartao_credito_id', $cartaoId)
    ->where('pago', false)
    ->selectRaw("DATE_FORMAT(data, '%Y-%m') as mes, COUNT(*) as qtd, SUM(valor) as total")
    ->groupBy('mes')
    ->orderBy('mes', 'desc')
    ->get();

$totalNaoPago = 0;
foreach ($porMes as $m) {
    echo "  {$m->mes}: {$m->qtd} lançamentos - R$ " . number_format($m->total, 2, ',', '.') . PHP_EOL;
    $totalNaoPago += $m->total;
}
echo "  TOTAL NÃO PAGO: R$ " . number_format($totalNaoPago, 2, ',', '.') . PHP_EOL;
echo PHP_EOL;

// Calcular fatura atual (mês atual)
$hoje = new DateTime();
$mesAtual = $hoje->format('Y-m');
$diaFechamento = $cartao->dia_fechamento ?? 1;

// Determinar período da fatura atual
if ((int)$hoje->format('d') <= $diaFechamento) {
    // Ainda estamos antes do fechamento, fatura do mês anterior
    $inicioFatura = (clone $hoje)->modify('first day of last month')->setDate(
        (int)(clone $hoje)->modify('first day of last month')->format('Y'),
        (int)(clone $hoje)->modify('first day of last month')->format('m'),
        $diaFechamento + 1
    );
    $fimFatura = (clone $hoje)->setDate((int)$hoje->format('Y'), (int)$hoje->format('m'), $diaFechamento);
} else {
    // Já passou o fechamento, fatura do mês atual
    $inicioFatura = (clone $hoje)->setDate((int)$hoje->format('Y'), (int)$hoje->format('m'), $diaFechamento + 1);
    $fimFatura = (clone $hoje)->modify('first day of next month')->setDate(
        (int)(clone $hoje)->modify('first day of next month')->format('Y'),
        (int)(clone $hoje)->modify('first day of next month')->format('m'),
        $diaFechamento
    );
}

echo "=== Período da Fatura Atual ===" . PHP_EOL;
echo "  De: " . $inicioFatura->format('Y-m-d') . " até " . $fimFatura->format('Y-m-d') . PHP_EOL;

// Calcular o que REALMENTE compromete o limite:
// - Lançamentos não pagos com data até o fechamento da fatura atual
$dataLimite = $fimFatura->format('Y-m-d');
$faturaAberta = DB::table('lancamentos')
    ->where('cartao_credito_id', $cartaoId)
    ->where('pago', false)
    ->where('data', '<=', $dataLimite)
    ->sum('valor');

echo "  Valor que compromete o limite (até {$dataLimite}): R$ " . number_format($faturaAberta, 2, ',', '.') . PHP_EOL;
echo PHP_EOL;

// Sugestão de limite correto
$limiteCorreto = $cartao->limite_total - $faturaAberta;
echo "=== SUGESTÃO ===" . PHP_EOL;
echo "  Limite Total: R$ " . number_format($cartao->limite_total, 2, ',', '.') . PHP_EOL;
echo "  Gasto não pago (até fatura atual): R$ " . number_format($faturaAberta, 2, ',', '.') . PHP_EOL;
echo "  Limite Disponível deveria ser: R$ " . number_format($limiteCorreto, 2, ',', '.') . PHP_EOL;
