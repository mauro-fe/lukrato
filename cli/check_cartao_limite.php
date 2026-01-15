#!/usr/bin/env php
<?php
/**
 * Script para verificar e recalcular limite disponível dos cartões
 */

require_once dirname(__DIR__) . '/bootstrap.php';

use Application\Models\CartaoCredito;
use Application\Models\Lancamento;

$userId = $argv[1] ?? 1;

echo "=== Verificando cartões do usuário {$userId} ===" . PHP_EOL . PHP_EOL;

$cartoes = CartaoCredito::where('user_id', $userId)->get();

if ($cartoes->isEmpty()) {
    echo "Nenhum cartão encontrado para o usuário {$userId}" . PHP_EOL;
    exit(1);
}

$totalCorrigidos = 0;

foreach ($cartoes as $cartao) {
    echo "📌 Cartão: {$cartao->nome_cartao} (ID: {$cartao->id})" . PHP_EOL;
    echo "   Limite Total: R$ " . number_format($cartao->limite_total, 2, ',', '.') . PHP_EOL;
    echo "   Limite Disponível (atual): R$ " . number_format($cartao->limite_disponivel, 2, ',', '.') . PHP_EOL;

    // Verificar lançamentos não pagos (usando a coluna correta: cartao_credito_id)
    $lancamentosNaoPagos = Lancamento::where('cartao_credito_id', $cartao->id)
        ->get();

    $totalNaoPago = $lancamentosNaoPagos->sum('valor');

    echo "   Lançamentos não pagos: " . $lancamentosNaoPagos->count() . PHP_EOL;
    echo "   Total não pago: R$ " . number_format($totalNaoPago, 2, ',', '.') . PHP_EOL;

    $limiteCorreto = $cartao->limite_total - $totalNaoPago;
    echo "   Limite que deveria ser: R$ " . number_format($limiteCorreto, 2, ',', '.') . PHP_EOL;

    if (abs($cartao->limite_disponivel - $limiteCorreto) > 0.01) {
        echo "   ⚠️  DIFERENÇA ENCONTRADA! Corrigindo..." . PHP_EOL;

        // Corrigir usando o método do modelo
        $cartao->atualizarLimiteDisponivel();
        $cartao->refresh();

        echo "   ✅ Limite atualizado para: R$ " . number_format($cartao->limite_disponivel, 2, ',', '.') . PHP_EOL;
        $totalCorrigidos++;
    } else {
        echo "   ✅ Limite está correto!" . PHP_EOL;
    }

    echo PHP_EOL;
}

echo "=============================================" . PHP_EOL;
echo "Total de cartões verificados: " . $cartoes->count() . PHP_EOL;
echo "Total de cartões corrigidos: {$totalCorrigidos}" . PHP_EOL;
