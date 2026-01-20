<?php

/**
 * Criar lançamentos corretos para itens de fatura já pagos
 */

require_once __DIR__ . '/../bootstrap.php';

use Application\Models\FaturaCartaoItem;
use Application\Models\Lancamento;
use Application\Models\CartaoCredito;

echo "\n✅ CORREÇÃO: CRIAR LANÇAMENTOS CORRETOS DOS ITENS\n";
echo "════════════════════════════════════════════════════════\n\n";

// Buscar itens pagos que não têm lançamento vinculado
$itensPagos = FaturaCartaoItem::where('pago', true)
    ->whereNull('lancamento_id')
    ->with('cartao')
    ->orderBy('data_pagamento', 'asc')
    ->get();

echo "📋 Itens pagos sem lançamento: {$itensPagos->count()}\n\n";

if ($itensPagos->isEmpty()) {
    echo "✅ Todos os itens pagos já têm lançamentos vinculados!\n\n";
    exit(0);
}

$lancamentosCriados = 0;
$erros = 0;

foreach ($itensPagos as $item) {
    $cartao = $item->cartao;

    if (!$cartao) {
        echo "⚠️  Item ID {$item->id}: Cartão não encontrado\n";
        $erros++;
        continue;
    }

    if (!$cartao->conta_id) {
        echo "⚠️  Item ID {$item->id}: Cartão '{$cartao->nome_cartao}' não está vinculado a uma conta\n";
        $erros++;
        continue;
    }

    try {
        // Criar lançamento na data do pagamento
        $dataPagamento = $item->data_pagamento ? date('Y-m-d', strtotime($item->data_pagamento)) : date('Y-m-d');

        $lancamento = Lancamento::create([
            'user_id' => $item->user_id,
            'conta_id' => $cartao->conta_id,
            'categoria_id' => $item->categoria_id,
            'tipo' => 'despesa',
            'valor' => $item->valor,
            'descricao' => $item->descricao,
            'data' => $dataPagamento,
            'observacao' => sprintf(
                'Fatura %s •••• %s - %02d/%04d',
                $cartao->nome_cartao,
                $cartao->ultimos_digitos,
                $item->mes_referencia,
                $item->ano_referencia
            ),
            'pago' => true,
            'data_pagamento' => $dataPagamento,
            'created_at' => $dataPagamento, // Data retroativa
            'updated_at' => now(),
        ]);

        // Vincular o item ao lançamento
        $item->lancamento_id = $lancamento->id;
        $item->save();

        echo "✅ Item ID {$item->id}: Lançamento ID {$lancamento->id} criado - {$item->descricao} (R$ {$item->valor})\n";
        $lancamentosCriados++;
    } catch (\Exception $e) {
        echo "❌ Erro ao criar lançamento para item ID {$item->id}: {$e->getMessage()}\n";
        $erros++;
    }
}

echo "\n════════════════════════════════════════════════════════\n";
echo "📊 RESULTADO:\n";
echo "   ✅ Lançamentos criados: {$lancamentosCriados}\n";
if ($erros > 0) {
    echo "   ❌ Erros: {$erros}\n";
}
echo "════════════════════════════════════════════════════════\n\n";

echo "🎉 Correção concluída!\n";
echo "   Agora os itens pagos aparecem como lançamentos individuais\n";
echo "   na data em que foram pagos.\n\n";
