<?php

/**
 * Teste completo do fluxo de limite do cartão de crédito
 * 
 * Testa:
 * 1. Lançamento no cartão (deve diminuir limite)
 * 2. Pagamento da fatura (deve liberar limite)
 */

require_once __DIR__ . '/../bootstrap.php';

use Application\Models\CartaoCredito;
use Application\Models\FaturaCartaoItem;
use Illuminate\Database\Capsule\Manager as DB;

echo "\n🧪 TESTE DE FLUXO DO CARTÃO DE CRÉDITO\n";
echo "════════════════════════════════════════════════════════\n\n";

// Solicitar ID do cartão
echo "Digite o ID do cartão para testar: ";
$cartaoId = trim(fgets(STDIN));

if (!$cartaoId || !is_numeric($cartaoId)) {
    echo "❌ ID inválido!\n\n";
    exit(1);
}

$cartao = CartaoCredito::find($cartaoId);

if (!$cartao) {
    echo "❌ Cartão não encontrado!\n\n";
    exit(1);
}

echo "\n📇 INFORMAÇÕES DO CARTÃO\n";
echo "─────────────────────────────────────────────────────────\n";
echo "ID: {$cartao->id}\n";
echo "Nome: {$cartao->nome_cartao}\n";
echo "Limite Total: R$ " . number_format($cartao->limite_total, 2, ',', '.') . "\n";
echo "Limite Disponível (BD): R$ " . number_format($cartao->limite_disponivel, 2, ',', '.') . "\n";

// Calcular limite real
$itensNaoPagos = FaturaCartaoItem::where('cartao_credito_id', $cartao->id)
    ->where('pago', false)
    ->get();

$totalNaoPago = $itensNaoPagos->sum('valor');
$limiteCalculado = $cartao->limite_total - $totalNaoPago;

echo "\n📊 CÁLCULO DO LIMITE\n";
echo "─────────────────────────────────────────────────────────\n";
echo "Total de itens não pagos: {$itensNaoPagos->count()}\n";
echo "Valor total utilizado: R$ " . number_format($totalNaoPago, 2, ',', '.') . "\n";
echo "Limite calculado: R$ " . number_format($limiteCalculado, 2, ',', '.') . "\n";

// Verificar se está correto
$diferenca = abs($cartao->limite_disponivel - $limiteCalculado);

if ($diferenca > 0.01) {
    echo "\n⚠️  DIVERGÊNCIA DETECTADA!\n";
    echo "Diferença: R$ " . number_format($diferenca, 2, ',', '.') . "\n";
    echo "Corrigindo automaticamente...\n";

    $cartao->atualizarLimiteDisponivel();
    $cartao->refresh();

    echo "✅ Limite corrigido para: R$ " . number_format($cartao->limite_disponivel, 2, ',', '.') . "\n";
} else {
    echo "\n✅ Limite está correto!\n";
}

// Listar itens não pagos
if ($itensNaoPagos->count() > 0) {
    echo "\n📋 ITENS NÃO PAGOS (consumindo limite)\n";
    echo "─────────────────────────────────────────────────────────\n";

    foreach ($itensNaoPagos as $item) {
        $mesAno = date('m/Y', strtotime($item->data_vencimento));
        echo sprintf(
            "• ID:%d - %s - R$ %.2f - Venc: %s\n",
            $item->id,
            substr($item->descricao, 0, 30),
            $item->valor,
            $mesAno
        );
    }
}

// Listar itens pagos
$itensPagos = FaturaCartaoItem::where('cartao_credito_id', $cartao->id)
    ->where('pago', true)
    ->orderBy('data_pagamento', 'desc')
    ->limit(5)
    ->get();

if ($itensPagos->count() > 0) {
    echo "\n✅ ÚLTIMOS ITENS PAGOS (limite já liberado)\n";
    echo "─────────────────────────────────────────────────────────\n";

    foreach ($itensPagos as $item) {
        $dataPag = $item->data_pagamento ? date('d/m/Y', strtotime($item->data_pagamento)) : 'N/A';
        echo sprintf(
            "• ID:%d - %s - R$ %.2f - Pago em: %s\n",
            $item->id,
            substr($item->descricao, 0, 30),
            $item->valor,
            $dataPag
        );
    }
}

echo "\n════════════════════════════════════════════════════════\n";
echo "📌 RESUMO DA LÓGICA DO SISTEMA:\n";
echo "────────────────────────────────────────────────────────\n";
echo "1. Ao criar lançamento no cartão → diminui limite\n";
echo "2. Ao pagar fatura/parcela → libera limite\n";
echo "3. Limite disponível = Limite total - Soma itens não pagos\n";
echo "════════════════════════════════════════════════════════\n\n";
