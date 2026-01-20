<?php

/**
 * Teste de pagamento de fatura - simular o processo
 */

require_once __DIR__ . '/../bootstrap.php';

use Application\Models\FaturaCartaoItem;
use Application\Models\Lancamento;
use Application\Models\CartaoCredito;

echo "\n🔍 ANÁLISE DO PROBLEMA DE PAGAMENTO\n";
echo "════════════════════════════════════════════════════════\n\n";

// Pegar um item pago
$itemPago = FaturaCartaoItem::where('pago', true)->first();

if (!$itemPago) {
    echo "❌ Nenhum item pago encontrado para análise.\n\n";
    exit(0);
}

echo "📋 Item analisado:\n";
echo "   ID: {$itemPago->id}\n";
echo "   Descrição: {$itemPago->descricao}\n";
echo "   Valor: R$ {$itemPago->valor}\n";
echo "   Data de pagamento: {$itemPago->data_pagamento}\n";
echo "   Cartão ID: {$itemPago->cartao_credito_id}\n";
echo "   User ID: {$itemPago->user_id}\n\n";

// Buscar o cartão
$cartao = CartaoCredito::find($itemPago->cartao_credito_id);

if (!$cartao) {
    echo "❌ Cartão não encontrado!\n\n";
    exit(1);
}

echo "💳 Cartão:\n";
echo "   ID: {$cartao->id}\n";
echo "   Nome: {$cartao->nome_cartao}\n";
echo "   Últimos dígitos: {$cartao->ultimos_digitos}\n";
echo "   Conta ID: " . ($cartao->conta_id ?? 'NULL') . "\n";
echo "   Limite disponível: R$ {$cartao->limite_disponivel}\n\n";

if (!$cartao->conta_id) {
    echo "⚠️  PROBLEMA ENCONTRADO!\n";
    echo "   O cartão NÃO está vinculado a nenhuma conta!\n";
    echo "   Isso faz com que o pagamento falhe na linha 131 do CartaoFaturaService:\n";
    echo "   throw new \\Exception('Cartão não está vinculado a nenhuma conta.');\n\n";
    echo "🔧 SOLUÇÃO:\n";
    echo "   O usuário precisa vincular o cartão a uma conta antes de pagar a fatura.\n\n";

    // Verificar se há contas disponíveis
    $contas = \Application\Models\Conta::where('user_id', $cartao->user_id)->get();

    if ($contas->isEmpty()) {
        echo "   ❌ O usuário não tem nenhuma conta criada!\n";
        echo "   Primeiro é necessário criar uma conta.\n\n";
    } else {
        echo "   ✅ Contas disponíveis para vincular:\n";
        foreach ($contas as $conta) {
            echo "      • ID {$conta->id}: {$conta->nome} ({$conta->instituicao})\n";
        }
        echo "\n";
    }
} else {
    echo "✅ Cartão está vinculado à conta ID: {$cartao->conta_id}\n\n";

    // Buscar lançamento que deveria ter sido criado
    $dataPagamento = date('Y-m-d', strtotime($itemPago->data_pagamento));
    $lancamento = Lancamento::where('user_id', $itemPago->user_id)
        ->where('conta_id', $cartao->conta_id)
        ->whereDate('data', $dataPagamento)
        ->where('descricao', 'like', '%Pagamento Fatura%')
        ->first();

    if (!$lancamento) {
        echo "❌ PROBLEMA: Não há lançamento correspondente!\n";
        echo "   Mesmo com o cartão vinculado a uma conta, o lançamento não foi criado.\n\n";
    } else {
        echo "✅ Lançamento encontrado!\n";
        echo "   ID: {$lancamento->id}\n";
        echo "   Descrição: {$lancamento->descricao}\n";
        echo "   Valor: R$ {$lancamento->valor}\n\n";
    }
}

echo "📊 RESUMO DO DIAGNÓSTICO:\n";
echo "════════════════════════════════════════════════════════\n";
echo "• Itens marcados como pagos: SIM ✅\n";
echo "• Lançamento criado: " . (isset($lancamento) && $lancamento ? "SIM ✅" : "NÃO ❌") . "\n";
echo "• Cartão vinculado à conta: " . ($cartao->conta_id ? "SIM ✅" : "NÃO ❌") . "\n\n";
