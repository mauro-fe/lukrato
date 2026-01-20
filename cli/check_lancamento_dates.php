<?php
require_once __DIR__ . '/../bootstrap.php';

use Application\Models\FaturaCartaoItem;
use Application\Models\Lancamento;

echo "\n🔍 VERIFICANDO DATAS DOS LANÇAMENTOS\n";
echo "════════════════════════════════════════════════════════\n\n";

$hoje = date('Y-m-d');
$mesAtual = date('Y-m');

echo "Data de hoje: {$hoje}\n";
echo "Mês atual: {$mesAtual}\n\n";

// Buscar itens pagos hoje
$itensPagosHoje = FaturaCartaoItem::where('pago', true)
    ->whereDate('data_pagamento', $hoje)
    ->with('lancamento')
    ->get();

echo "📋 Itens pagos hoje: {$itensPagosHoje->count()}\n\n";

if ($itensPagosHoje->isEmpty()) {
    echo "ℹ️  Nenhum item foi pago hoje.\n";
    echo "   Verificando itens pagos recentemente...\n\n";

    $itensPagosRecentes = FaturaCartaoItem::where('pago', true)
        ->whereNotNull('data_pagamento')
        ->with('lancamento')
        ->orderBy('data_pagamento', 'desc')
        ->limit(10)
        ->get();

    echo "📋 Últimos 10 itens pagos:\n\n";

    foreach ($itensPagosRecentes as $item) {
        $dataPagamento = date('Y-m-d', strtotime($item->data_pagamento));
        $lancamento = $item->lancamento;

        echo "Item ID {$item->id}:\n";
        echo "  • Descrição: {$item->descricao}\n";
        echo "  • Valor: R$ {$item->valor}\n";
        echo "  • Pago em: {$dataPagamento}\n";

        if ($lancamento) {
            $dataLancamento = date('Y-m-d', strtotime($lancamento->data));
            $mesLancamento = date('Y-m', strtotime($lancamento->data));

            echo "  • Lançamento ID: {$lancamento->id}\n";
            echo "  • Data do lançamento: {$dataLancamento}\n";
            echo "  • Mês do lançamento: {$mesLancamento}\n";

            if ($dataPagamento != $dataLancamento) {
                echo "  ❌ PROBLEMA: Data do lançamento diferente da data de pagamento!\n";
            } else {
                echo "  ✅ OK: Lançamento na data correta\n";
            }
        } else {
            echo "  ⚠️  Sem lançamento vinculado\n";
        }
        echo "\n";
    }

    exit(0);
}

foreach ($itensPagosHoje as $item) {
    $lancamento = $item->lancamento;

    echo "Item ID {$item->id}: {$item->descricao} - R$ {$item->valor}\n";

    if ($lancamento) {
        $dataLancamento = date('Y-m-d', strtotime($lancamento->data));
        $mesLancamento = date('Y-m', strtotime($lancamento->data));

        echo "  • Lançamento ID: {$lancamento->id}\n";
        echo "  • Data: {$dataLancamento}\n";
        echo "  • Mês: {$mesLancamento}\n";

        if ($dataLancamento != $hoje) {
            echo "  ❌ ERRO: Lançamento não está em {$hoje}!\n";
        } else if ($mesLancamento != $mesAtual) {
            echo "  ❌ ERRO: Lançamento não está no mês {$mesAtual}!\n";
        } else {
            echo "  ✅ OK\n";
        }
    } else {
        echo "  ⚠️  Item sem lançamento vinculado\n";
    }
    echo "\n";
}

echo "════════════════════════════════════════════════════════\n\n";
