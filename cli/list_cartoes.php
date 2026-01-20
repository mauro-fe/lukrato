<?php
require_once __DIR__ . '/../bootstrap.php';

use Application\Models\CartaoCredito;
use Application\Models\FaturaCartaoItem;

$cartaoId = $argv[1] ?? null;

if ($cartaoId) {
    // Diagnóstico detalhado de um cartão específico
    $cartao = CartaoCredito::find($cartaoId);

    if (!$cartao) {
        echo "Cartão não encontrado!\n";
        exit(1);
    }

    echo "\n📊 DIAGNÓSTICO DO CARTÃO ID: {$cartaoId}\n";
    echo "═══════════════════════════════════════════════════════\n";
    echo "Nome: {$cartao->nome_cartao}\n";
    echo "Limite Total: R$ " . number_format($cartao->limite_total, 2, ',', '.') . "\n";
    echo "Limite Disponível (BD): R$ " . number_format($cartao->limite_disponivel, 2, ',', '.') . "\n";

    $itens = FaturaCartaoItem::where('cartao_credito_id', $cartaoId)->get();

    echo "\n📋 ITENS DE FATURA ({$itens->count()} itens)\n";
    echo "───────────────────────────────────────────────────────\n";

    $totalNaoPago = 0;
    $totalPago = 0;

    foreach ($itens as $item) {
        $status = $item->pago ? '✅ PAGO' : '❌ NÃO PAGO';
        echo "ID: {$item->id} | {$status} | R$ " . number_format($item->valor, 2, ',', '.') . " | {$item->descricao}\n";

        if ($item->pago) {
            $totalPago += $item->valor;
        } else {
            $totalNaoPago += $item->valor;
        }
    }

    echo "\n📈 RESUMO\n";
    echo "───────────────────────────────────────────────────────\n";
    echo "Total PAGO: R$ " . number_format($totalPago, 2, ',', '.') . "\n";
    echo "Total NÃO PAGO: R$ " . number_format($totalNaoPago, 2, ',', '.') . "\n";

    $limiteCorreto = $cartao->limite_total - $totalNaoPago;
    echo "Limite CORRETO: R$ " . number_format($limiteCorreto, 2, ',', '.') . "\n";

    if (abs($cartao->limite_disponivel - $limiteCorreto) > 0.01) {
        echo "\n⚠️  LIMITE INCORRETO! Corrigindo...\n";
        $cartao->atualizarLimiteDisponivel();
        $cartao->refresh();
        echo "✅ Limite corrigido para: R$ " . number_format($cartao->limite_disponivel, 2, ',', '.') . "\n";
    } else {
        echo "\n✅ Limite está correto!\n";
    }
} else {
    // Listar todos os cartões
    echo "\nCartões disponíveis:\n";
    foreach (CartaoCredito::all() as $c) {
        echo $c->id . ' - ' . $c->nome_cartao . " | Limite: R$ " . number_format($c->limite_total, 2, ',', '.') . " | Disponível: R$ " . number_format($c->limite_disponivel, 2, ',', '.') . "\n";
    }
    echo "\nUso: php list_cartoes.php [ID] para diagnóstico detalhado\n";
}
