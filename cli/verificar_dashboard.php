<?php
require_once __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

$userId = 1;

echo "=== LANÇAMENTOS SEM CARTÃO ===\n\n";

$semCartao = DB::table('lancamentos')
    ->where('user_id', $userId)
    ->whereNull('cartao_credito_id')
    ->where('eh_transferencia', 0)
    ->selectRaw('tipo, afeta_caixa, COUNT(*) as total, SUM(valor) as valor')
    ->groupBy('tipo', 'afeta_caixa')
    ->get();

foreach ($semCartao as $l) {
    $afeta = $l->afeta_caixa ? 'Afeta caixa' : 'NÃO afeta caixa';
    if ($l->afeta_caixa === null) $afeta = 'NULL (afeta por padrão)';
    echo strtoupper($l->tipo) . " | {$afeta} | {$l->total} lanç. | R$ " . number_format($l->valor, 2, ',', '.') . "\n";
}

echo "\n=== VERIFICAÇÃO DASHBOARD ===\n\n";

// Saldo das contas
$contas = DB::table('contas')->where('user_id', $userId)->get();
$saldoContas = 0;
echo "--- CONTAS ---\n";
foreach ($contas as $c) {
    echo "{$c->nome}: R$ " . number_format($c->saldo_inicial ?? 0, 2, ',', '.') . "\n";
    $saldoContas += $c->saldo_inicial ?? 0;
}
echo "Total saldo inicial: R$ " . number_format($saldoContas, 2, ',', '.') . "\n\n";

// Janeiro 2026
echo "--- JANEIRO 2026 ---\n";

// Receitas do mês (afeta_caixa)
$receitas = DB::table('lancamentos')
    ->where('user_id', $userId)
    ->where('tipo', 'receita')
    ->where('eh_transferencia', 0)
    ->where(function ($q) {
        $q->where('afeta_caixa', true)->orWhereNull('afeta_caixa');
    })
    ->whereBetween('data', ['2026-01-01', '2026-01-31'])
    ->sum('valor');

echo "Receitas (caixa): R$ " . number_format($receitas, 2, ',', '.') . "\n";

// Despesas do mês (afeta_caixa)
$despesas = DB::table('lancamentos')
    ->where('user_id', $userId)
    ->where('tipo', 'despesa')
    ->where('eh_transferencia', 0)
    ->where(function ($q) {
        $q->where('afeta_caixa', true)->orWhereNull('afeta_caixa');
    })
    ->whereBetween('data', ['2026-01-01', '2026-01-31'])
    ->sum('valor');

echo "Despesas (caixa): R$ " . number_format($despesas, 2, ',', '.') . "\n";
echo "Resultado: R$ " . number_format($receitas - $despesas, 2, ',', '.') . "\n\n";

// Verificar lançamentos de cartão
echo "--- LANÇAMENTOS DE CARTÃO (todos) ---\n";
$lancCartao = DB::table('lancamentos')
    ->where('user_id', $userId)
    ->whereNotNull('cartao_credito_id')
    ->selectRaw('pago, afeta_caixa, COUNT(*) as total, SUM(valor) as valor')
    ->groupBy('pago', 'afeta_caixa')
    ->get();

foreach ($lancCartao as $l) {
    $pago = $l->pago ? 'PAGO' : 'PENDENTE';
    $afeta = $l->afeta_caixa ? 'Afeta caixa' : 'NÃO afeta caixa';
    if ($l->afeta_caixa === null) $afeta = 'NULL (afeta por padrão)';
    echo "{$pago} | {$afeta} | {$l->total} lanç. | R$ " . number_format($l->valor, 2, ',', '.') . "\n";
}

echo "\n--- ITENS FATURA ---\n";
$itensFatura = DB::table('faturas_cartao_itens')
    ->where('user_id', $userId)
    ->selectRaw('pago, COUNT(*) as total, SUM(valor) as valor')
    ->groupBy('pago')
    ->get();

foreach ($itensFatura as $i) {
    $pago = $i->pago ? 'PAGO' : 'PENDENTE';
    echo "{$pago} | {$i->total} itens | R$ " . number_format($i->valor, 2, ',', '.') . "\n";
}

// Saldo calculado
echo "\n--- SALDO CALCULADO ---\n";
$todasReceitas = DB::table('lancamentos')
    ->where('user_id', $userId)
    ->where('tipo', 'receita')
    ->where('eh_transferencia', 0)
    ->where(function ($q) {
        $q->where('afeta_caixa', true)->orWhereNull('afeta_caixa');
    })
    ->sum('valor');

$todasDespesas = DB::table('lancamentos')
    ->where('user_id', $userId)
    ->where('tipo', 'despesa')
    ->where('eh_transferencia', 0)
    ->where(function ($q) {
        $q->where('afeta_caixa', true)->orWhereNull('afeta_caixa');
    })
    ->sum('valor');

$saldoCalculado = $saldoContas + $todasReceitas - $todasDespesas;
echo "Saldo inicial contas: R$ " . number_format($saldoContas, 2, ',', '.') . "\n";
echo "Total receitas (caixa): R$ " . number_format($todasReceitas, 2, ',', '.') . "\n";
echo "Total despesas (caixa): R$ " . number_format($todasDespesas, 2, ',', '.') . "\n";
echo "SALDO CALCULADO: R$ " . number_format($saldoCalculado, 2, ',', '.') . "\n";
