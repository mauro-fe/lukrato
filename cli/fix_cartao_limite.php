#!/usr/bin/env php
<?php
/**
 * Script para corrigir limite do cartão baseado em faturas não pagas
 */

require_once dirname(__DIR__) . '/bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;
use Application\Models\CartaoCredito;
use Application\Models\FaturaCartaoItem;

$cartaoId = $argv[1] ?? 32;

echo "=== Análise de Faturas do Cartão ID: {$cartaoId} ===" . PHP_EOL . PHP_EOL;

$cartao = CartaoCredito::find($cartaoId);
if (!$cartao) {
    echo "Cartão não encontrado!" . PHP_EOL;
    exit(1);
}

echo "📌 Cartão: {$cartao->nome_cartao}" . PHP_EOL;
echo "   Limite Total: R$ " . number_format($cartao->limite_total, 2, ',', '.') . PHP_EOL;
echo "   Limite Disponível (atual): R$ " . number_format($cartao->limite_disponivel, 2, ',', '.') . PHP_EOL;
echo PHP_EOL;

// Verificar itens de fatura na tabela correta
echo "=== Itens de Fatura (faturas_cartao_itens) ===" . PHP_EOL;
$itens = DB::table('faturas_cartao_itens')
    ->where('cartao_credito_id', $cartaoId)
    ->selectRaw('pago, COUNT(*) as qtd, SUM(valor) as total')
    ->groupBy('pago')
    ->get();

$totalNaoPagoFaturas = 0;
foreach ($itens as $i) {
    $status = $i->pago ? 'PAGOS' : 'NÃO PAGOS';
    echo "  {$status}: {$i->qtd} itens - R$ " . number_format($i->total, 2, ',', '.') . PHP_EOL;
    if (!$i->pago) {
        $totalNaoPagoFaturas = $i->total;
    }
}
echo PHP_EOL;

// Por mês de vencimento
echo "=== Itens NÃO PAGOS por Mês de Vencimento ===" . PHP_EOL;
$porMes = DB::table('faturas_cartao_itens')
    ->where('cartao_credito_id', $cartaoId)
    ->where('pago', false)
    ->selectRaw("DATE_FORMAT(data_vencimento, '%Y-%m') as mes, COUNT(*) as qtd, SUM(valor) as total")
    ->groupBy('mes')
    ->orderBy('mes')
    ->get();

foreach ($porMes as $m) {
    echo "  {$m->mes}: {$m->qtd} itens - R$ " . number_format($m->total, 2, ',', '.') . PHP_EOL;
}
echo PHP_EOL;

// Calcular limite correto baseado nos itens de fatura não pagos
$limiteCorreto = $cartao->limite_total - $totalNaoPagoFaturas;

echo "=== CÁLCULO CORRETO ===" . PHP_EOL;
echo "  Limite Total: R$ " . number_format($cartao->limite_total, 2, ',', '.') . PHP_EOL;
echo "  Total em Faturas Não Pagas: R$ " . number_format($totalNaoPagoFaturas, 2, ',', '.') . PHP_EOL;
echo "  Limite Disponível CORRETO: R$ " . number_format($limiteCorreto, 2, ',', '.') . PHP_EOL;
echo PHP_EOL;

if (abs($cartao->limite_disponivel - $limiteCorreto) > 0.01) {
    echo "⚠️  DIFERENÇA ENCONTRADA!" . PHP_EOL;
    echo "   Atual: R$ " . number_format($cartao->limite_disponivel, 2, ',', '.') . PHP_EOL;
    echo "   Deveria ser: R$ " . number_format($limiteCorreto, 2, ',', '.') . PHP_EOL;

    if (isset($argv[2]) && $argv[2] === '--fix') {
        $cartao->limite_disponivel = $limiteCorreto;
        $cartao->save();
        echo PHP_EOL . "✅ CORRIGIDO! Novo limite: R$ " . number_format($cartao->limite_disponivel, 2, ',', '.') . PHP_EOL;
    } else {
        echo PHP_EOL . "💡 Para corrigir, execute: php cli/fix_cartao_limite.php {$cartaoId} --fix" . PHP_EOL;
    }
} else {
    echo "✅ Limite está correto!" . PHP_EOL;
}
