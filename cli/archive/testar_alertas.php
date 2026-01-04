<?php

/**
 * Script para testar alertas de cartões
 */

require_once __DIR__ . '/../bootstrap.php';

use Application\Models\CartaoCredito;
use Application\Models\Lancamento;

$userId = isset($argv[1]) ? (int) $argv[1] : 1;

echo "🧪 CRIANDO CENÁRIOS DE TESTE PARA ALERTAS\n";
echo str_repeat("=", 60) . "\n\n";

try {
    // 1. Criar/Atualizar cartão com limite baixo
    echo "1️⃣  Criando cartão com limite baixo...\n";
    $cartao = CartaoCredito::where('user_id', $userId)->first();

    if ($cartao) {
        // Usar 95% do limite para gerar alerta
        $cartao->limite_disponivel = $cartao->limite_total * 0.05; // 5% disponível
        $cartao->save();
        echo "   ✅ Cartão '{$cartao->nome_cartao}' atualizado com 5% de limite disponível\n\n";
    }

    // 2. Criar lançamento não pago para o mês atual
    echo "2️⃣  Criando lançamento para gerar alerta de vencimento...\n";
    $hoje = new DateTime();
    $cartoes = CartaoCredito::where('user_id', $userId)
        ->where('ativo', true)
        ->get();

    foreach ($cartoes->take(2) as $cartao) {
        // Ajustar data para o dia de vencimento do cartão
        $diaVencimento = $cartao->dia_vencimento;
        $dataLancamento = clone $hoje;
        $dataLancamento->setDate(
            (int) $hoje->format('Y'),
            (int) $hoje->format('n'),
            min($diaVencimento - 5, (int) $hoje->format('j')) // 5 dias antes do vencimento
        );

        Lancamento::create([
            'user_id' => $userId,
            'cartao_credito_id' => $cartao->id,
            'descricao' => 'Compra Teste - Vencimento Próximo',
            'valor' => 150.00,
            'data' => $dataLancamento->format('Y-m-d'),
            'tipo' => 'despesa',
            'pago' => false,
            'eh_parcelado' => false,
        ]);

        echo "   ✅ Lançamento criado no cartão '{$cartao->nome_cartao}'\n";
        echo "      Data: " . $dataLancamento->format('d/m/Y') . "\n";
        echo "      Vencimento: {$diaVencimento}/{$hoje->format('m/Y')}\n\n";
    }

    echo str_repeat("=", 60) . "\n";
    echo "✅ Cenários criados com sucesso!\n\n";
    echo "📱 PRÓXIMOS PASSOS:\n";
    echo "   1. Acesse: /admin/cartoes\n";
    echo "   2. Os alertas devem aparecer no topo da página\n";
    echo "   3. Alertas de limite baixo: cartões com <20% disponível\n";
    echo "   4. Alertas de vencimento: faturas que vencem em até 7 dias\n\n";
} catch (Exception $e) {
    echo "\n❌ ERRO: " . $e->getMessage() . "\n";
    exit(1);
}
