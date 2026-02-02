<?php
/**
 * Script CLI para recalcular limites disponíveis de todos os cartões
 * 
 * Uso: php cli/recalcular_limites_cartoes.php
 * 
 * Este script recalcula o limite_disponivel de todos os cartões de crédito
 * baseado nos itens de fatura não pagos e estornos.
 */

require_once __DIR__ . '/../bootstrap.php';

use Application\Models\CartaoCredito;
use Illuminate\Database\Capsule\Manager as DB;

echo "=== Recalculador de Limites de Cartões ===\n\n";

// Buscar todos os cartões
$cartoes = CartaoCredito::all();
$totalCartoes = $cartoes->count();

echo "Encontrados {$totalCartoes} cartões para recalcular.\n\n";

$corrigidos = 0;
$semAlteracao = 0;

foreach ($cartoes as $cartao) {
    $limiteAnterior = $cartao->limite_disponivel;
    
    // Calcular limite utilizado (despesas não pagas - estornos)
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
    $novoLimiteDisponivel = $cartao->limite_total - $limiteUtilizado;

    if (abs($limiteAnterior - $novoLimiteDisponivel) > 0.01) {
        $cartao->limite_disponivel = $novoLimiteDisponivel;
        $cartao->save();
        
        echo "✓ Cartão #{$cartao->id} ({$cartao->nome_cartao}):\n";
        echo "  Limite Total: R$ " . number_format($cartao->limite_total, 2, ',', '.') . "\n";
        echo "  Despesas não pagas: R$ " . number_format($totalDespesasNaoPagas, 2, ',', '.') . "\n";
        echo "  Estornos: R$ " . number_format(abs($totalEstornos), 2, ',', '.') . "\n";
        echo "  Limite Anterior: R$ " . number_format($limiteAnterior, 2, ',', '.') . "\n";
        echo "  Limite Corrigido: R$ " . number_format($novoLimiteDisponivel, 2, ',', '.') . "\n\n";
        
        $corrigidos++;
    } else {
        $semAlteracao++;
    }
}

echo "=== Resumo ===\n";
echo "Total de cartões: {$totalCartoes}\n";
echo "Corrigidos: {$corrigidos}\n";
echo "Sem alteração: {$semAlteracao}\n";
echo "\n✅ Processo concluído!\n";
