<?php

require __DIR__ . '/../bootstrap.php';

echo "=== LISTANDO CARTOES ===\n\n";

$cartoes = \Application\Models\CartaoCredito::all();

foreach ($cartoes as $cartao) {
    echo sprintf(
        "ID: %d | User: %d | Nome: %s | Bandeira: %s | Ativo: %s\n",
        $cartao->id,
        $cartao->user_id,
        $cartao->nome_cartao ?? 'N/A',
        $cartao->bandeira ?? 'N/A',
        $cartao->ativo ? 'SIM' : 'NAO'
    );
}

echo "\n=== LISTANDO FATURAS JANEIRO 2026 ===\n\n";

$itens = \Application\Models\FaturaCartaoItem::whereYear('data_vencimento', 2026)
    ->whereMonth('data_vencimento', 1)
    ->get();

echo "Total de itens em Janeiro/2026: " . $itens->count() . "\n\n";

foreach ($itens as $item) {
    echo sprintf(
        "ID: %d | Cartao: %d | Valor: R$ %.2f | Pago: %s | Desc: %s\n",
        $item->id,
        $item->cartao_credito_id,
        $item->valor,
        $item->pago ? 'SIM' : 'NAO',
        substr($item->descricao, 0, 40)
    );
}

// Agrupar por cartão
echo "\n=== TOTAIS POR CARTAO ===\n\n";
$porCartao = $itens->groupBy('cartao_credito_id');

foreach ($porCartao as $cartaoId => $itensCartao) {
    $pendentes = $itensCartao->where('pago', false);
    $totalPendente = $pendentes->sum('valor');

    echo sprintf(
        "Cartao ID: %d | Itens Pendentes: %d | Total Pendente: R$ %.2f\n",
        $cartaoId,
        $pendentes->count(),
        $totalPendente
    );
}
