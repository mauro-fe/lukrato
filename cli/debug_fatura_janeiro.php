<?php

require __DIR__ . '/../bootstrap.php';

$userId = 1;
$cartaoId = 1;
$mes = 1;
$ano = 2026;

echo "=== DEBUG FATURA JANEIRO 2026 ===\n\n";

// Buscar cartão
$cartao = \Application\Models\CartaoCredito::find($cartaoId);
if (!$cartao) {
    echo "Cartao nao encontrado!\n";
    exit;
}

echo "Cartao: {$cartao->nome_cartao}\n";
echo "User ID: {$cartao->user_id}\n\n";

// Buscar itens da fatura
$itens = \Application\Models\FaturaCartaoItem::where('cartao_credito_id', $cartaoId)
    ->whereYear('data_vencimento', $ano)
    ->whereMonth('data_vencimento', $mes)
    ->orderBy('data_compra')
    ->get();

echo "Total de itens encontrados: " . $itens->count() . "\n\n";

foreach ($itens as $item) {
    echo sprintf(
        "ID: %d | Descricao: %s | Valor: R$ %.2f | Pago: %s | Vencimento: %s\n",
        $item->id,
        substr($item->descricao, 0, 50),
        $item->valor,
        $item->pago ? 'SIM' : 'NAO',
        $item->data_vencimento
    );
}

// Calcular totais
$pendentes = $itens->where('pago', false);
$pagos = $itens->where('pago', true);

$totalPendente = $pendentes->sum('valor');
$totalPago = $pagos->sum('valor');
$totalGeral = $itens->sum('valor');

echo "\n=== TOTAIS ===\n";
echo "Itens Pendentes: " . $pendentes->count() . "\n";
echo "Total Pendente: R$ " . number_format($totalPendente, 2, ',', '.') . "\n\n";
echo "Itens Pagos: " . $pagos->count() . "\n";
echo "Total Pago: R$ " . number_format($totalPago, 2, ',', '.') . "\n\n";
echo "Total Geral: R$ " . number_format($totalGeral, 2, ',', '.') . "\n";
