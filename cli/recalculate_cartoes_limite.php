<?php

/**
 * Recalcula os limites disponíveis de todos os cartões de crédito
 * baseado nos itens de fatura não pagos e estornos
 */

require_once __DIR__ . '/../bootstrap.php';

use Application\Models\CartaoCredito;
use Illuminate\Database\Capsule\Manager as DB;

echo "=== Recalculando limites dos cartões de crédito ===\n\n";

try {
    $cartoes = CartaoCredito::all();
    $total = $cartoes->count();
    $atualizados = 0;

    echo "Total de cartões encontrados: {$total}\n\n";

    foreach ($cartoes as $cartao) {
        $limiteAnterior = $cartao->limite_disponivel;

        // Buscar totais de despesas e estornos
        $totalDespesasNaoPagas = DB::table('faturas_cartao_itens')
            ->where('cartao_credito_id', $cartao->id)
            ->where('pago', false)
            ->where('tipo', '!=', 'estorno')
            ->sum('valor');

        $totalEstornos = DB::table('faturas_cartao_itens')
            ->where('cartao_credito_id', $cartao->id)
            ->where('tipo', 'estorno')
            ->sum('valor'); // Já é negativo

        $limiteUtilizado = max(0, $totalDespesasNaoPagas + $totalEstornos);
        $novoLimite = $cartao->limite_total - $limiteUtilizado;

        if (abs($limiteAnterior - $novoLimite) > 0.01) {
            $cartao->limite_disponivel = $novoLimite;
            $cartao->save();
            $atualizados++;

            echo "✅ Cartão #{$cartao->id} ({$cartao->nome_cartao}):\n";
            echo "   Limite anterior: R$ " . number_format($limiteAnterior, 2, ',', '.') . "\n";
            echo "   Despesas não pagas: R$ " . number_format($totalDespesasNaoPagas, 2, ',', '.') . "\n";
            echo "   Estornos: R$ " . number_format($totalEstornos, 2, ',', '.') . "\n";
            echo "   Novo limite: R$ " . number_format($novoLimite, 2, ',', '.') . "\n\n";
        } else {
            echo "⏸️  Cartão #{$cartao->id} ({$cartao->nome_cartao}): limite já correto\n";
        }
    }

    echo "\n=== Resultado ===\n";
    echo "Total de cartões: {$total}\n";
    echo "Atualizados: {$atualizados}\n";
    echo "Sem alteração: " . ($total - $atualizados) . "\n";
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n✅ Processo concluído!\n";
