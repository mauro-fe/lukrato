<?php

/**
 * Analisar lançamentos da conta Pagar contas
 */

require_once __DIR__ . '/../bootstrap.php';

use Application\Models\Lancamento;
use Illuminate\Database\Capsule\Manager as DB;

echo "=== LANÇAMENTOS DA CONTA PAGAR CONTAS (ID 21) ===\n\n";

$userId = 1;
$contaId = 21;

// Todos os lançamentos que afetam esta conta
$lancamentos = Lancamento::where('user_id', $userId)
    ->where(function ($q) use ($contaId) {
        $q->where('conta_id', $contaId)
            ->orWhere('conta_id_destino', $contaId);
    })
    ->orderBy('data')
    ->get();

$somaReceitas = 0;
$somaDespesas = 0;
$somaTransfIn = 0;
$somaTransfOut = 0;

echo "=== DETALHES ===\n";
foreach ($lancamentos as $l) {
    $pago = $l->pago ? 'PAGO' : 'PEND';
    $cartao = $l->cartao_credito_id ? '[CARTAO]' : '';

    if ($l->eh_transferencia && $l->conta_id_destino == $contaId) {
        // Transferência entrando
        echo "TRANSF IN  | ID: {$l->id} | {$l->data} | +R$ " . number_format($l->valor, 2, ',', '.') . " | {$pago} | {$l->descricao}\n";
        $somaTransfIn += $l->valor;
    } elseif ($l->eh_transferencia && $l->conta_id == $contaId) {
        // Transferência saindo
        echo "TRANSF OUT | ID: {$l->id} | {$l->data} | -R$ " . number_format($l->valor, 2, ',', '.') . " | {$pago} | {$l->descricao}\n";
        $somaTransfOut += $l->valor;
    } elseif ($l->tipo == 'receita') {
        echo "RECEITA    | ID: {$l->id} | {$l->data} | +R$ " . number_format($l->valor, 2, ',', '.') . " | {$pago} {$cartao} | {$l->descricao}\n";
        $somaReceitas += $l->valor;
    } elseif ($l->tipo == 'despesa') {
        echo "DESPESA    | ID: {$l->id} | {$l->data} | -R$ " . number_format($l->valor, 2, ',', '.') . " | {$pago} {$cartao} | {$l->descricao}\n";
        $somaDespesas += $l->valor;
    }
}

echo "\n=== RESUMO ===\n";
echo "Receitas: +R$ " . number_format($somaReceitas, 2, ',', '.') . "\n";
echo "Despesas: -R$ " . number_format($somaDespesas, 2, ',', '.') . "\n";
echo "Transf. entrando: +R$ " . number_format($somaTransfIn, 2, ',', '.') . "\n";
echo "Transf. saindo: -R$ " . number_format($somaTransfOut, 2, ',', '.') . "\n";
$saldo = $somaReceitas - $somaDespesas + $somaTransfIn - $somaTransfOut;
echo "SALDO CALCULADO: R$ " . number_format($saldo, 2, ',', '.') . "\n";

// Buscar possíveis duplicados (mesma descrição, valor e data)
echo "\n=== POSSÍVEIS DUPLICADOS ===\n";
$grupos = $lancamentos->groupBy(function ($l) {
    return $l->descricao . '|' . $l->valor . '|' . substr($l->data, 0, 10);
});

foreach ($grupos as $chave => $grupo) {
    if ($grupo->count() > 1) {
        echo "Duplicado encontrado ({$grupo->count()}x): {$chave}\n";
        foreach ($grupo as $l) {
            echo "  ID: {$l->id} | pago: " . ($l->pago ? 'sim' : 'não') . " | cartao_id: " . ($l->cartao_credito_id ?: 'null') . "\n";
        }
    }
}
