<?php
require_once __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

$userId = 1;

echo "=== TODOS OS LANÇAMENTOS DE JANEIRO 2026 ===\n\n";

// Buscar TODOS os lançamentos de janeiro (sem filtros)
$lancamentos = DB::table('lancamentos')
    ->where('user_id', $userId)
    ->whereBetween('data', ['2026-01-01', '2026-01-31'])
    ->orderBy('data')
    ->orderBy('tipo')
    ->get();

$totalReceitas = 0;
$totalDespesas = 0;
$receitasCaixa = 0;
$despesasCaixa = 0;

echo "Data       | Tipo     | Valor      | Pago | AfetaCaixa | Transf | Cartão | Descrição\n";
echo str_repeat('-', 120) . "\n";

foreach ($lancamentos as $l) {
    $pago = $l->pago ? 'SIM' : 'NÃO';
    $afeta = $l->afeta_caixa ? 'SIM' : 'NÃO';
    if ($l->afeta_caixa === null) $afeta = 'NULL';
    $transf = $l->eh_transferencia ? 'SIM' : 'NÃO';
    $cartao = $l->cartao_credito_id ? "#{$l->cartao_credito_id}" : '-';
    $desc = substr($l->descricao ?? '', 0, 25);

    echo "{$l->data} | " . str_pad(strtoupper($l->tipo), 8) . " | R$ " . str_pad(number_format($l->valor, 2, ',', '.'), 8, ' ', STR_PAD_LEFT);
    echo " | {$pago}  | {$afeta}        | {$transf}    | " . str_pad($cartao, 6) . " | {$desc}\n";

    // Contabilizar
    if ($l->tipo === 'receita') {
        $totalReceitas += $l->valor;
        if (($l->afeta_caixa || $l->afeta_caixa === null) && !$l->eh_transferencia) {
            $receitasCaixa += $l->valor;
        }
    } elseif ($l->tipo === 'despesa') {
        $totalDespesas += $l->valor;
        if (($l->afeta_caixa || $l->afeta_caixa === null) && !$l->eh_transferencia) {
            $despesasCaixa += $l->valor;
        }
    }
}

echo "\n=== RESUMO JANEIRO 2026 ===\n\n";

echo "TOTAL (sem filtros):\n";
echo "  Receitas: R$ " . number_format($totalReceitas, 2, ',', '.') . "\n";
echo "  Despesas: R$ " . number_format($totalDespesas, 2, ',', '.') . "\n";
echo "  Resultado: R$ " . number_format($totalReceitas - $totalDespesas, 2, ',', '.') . "\n\n";

echo "CAIXA (afeta_caixa=true, não é transferência):\n";
echo "  Receitas: R$ " . number_format($receitasCaixa, 2, ',', '.') . "\n";
echo "  Despesas: R$ " . number_format($despesasCaixa, 2, ',', '.') . "\n";
echo "  Resultado: R$ " . number_format($receitasCaixa - $despesasCaixa, 2, ',', '.') . "\n\n";

// Verificar lançamentos excluídos do caixa
echo "=== LANÇAMENTOS NÃO CONTABILIZADOS NO CAIXA ===\n\n";

$excluidos = DB::table('lancamentos')
    ->where('user_id', $userId)
    ->whereBetween('data', ['2026-01-01', '2026-01-31'])
    ->where(function ($q) {
        $q->where('afeta_caixa', false)
            ->orWhere('eh_transferencia', true);
    })
    ->orderBy('data')
    ->get();

$excluidosReceitas = 0;
$excluidosDespesas = 0;

foreach ($excluidos as $l) {
    $motivo = [];
    if (!$l->afeta_caixa) $motivo[] = 'afeta_caixa=false';
    if ($l->eh_transferencia) $motivo[] = 'transferência';
    $cartao = $l->cartao_credito_id ? "Cartão #{$l->cartao_credito_id}" : '-';

    echo "{$l->data} | " . strtoupper($l->tipo) . " | R$ " . number_format($l->valor, 2, ',', '.') . " | " . implode(', ', $motivo) . " | {$cartao} | {$l->descricao}\n";

    if ($l->tipo === 'receita') $excluidosReceitas += $l->valor;
    if ($l->tipo === 'despesa') $excluidosDespesas += $l->valor;
}

echo "\nTotal excluído:\n";
echo "  Receitas não contabilizadas: R$ " . number_format($excluidosReceitas, 2, ',', '.') . "\n";
echo "  Despesas não contabilizadas: R$ " . number_format($excluidosDespesas, 2, ',', '.') . "\n";
