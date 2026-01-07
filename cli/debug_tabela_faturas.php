<?php

require __DIR__ . '/../bootstrap.php';

echo "=== FATURAS (PARCELAMENTOS) ===\n\n";

$faturas = \Application\Models\Fatura::where('user_id', 1)
    ->with('cartaoCredito')
    ->get();

echo "Total de faturas (parcelamentos): " . $faturas->count() . "\n\n";

foreach ($faturas as $fatura) {
    $itensPagos = $fatura->itens()->where('pago', 1)->count();
    $totalItens = $fatura->itens()->count();
    $valorPendente = $fatura->itens()->where('pago', 0)->sum('valor');

    echo sprintf(
        "ID: %d | Desc: %s | Valor Total: R$ %.2f | Valor Pendente: R$ %.2f | Itens: %d/%d pagos\n",
        $fatura->id,
        substr($fatura->descricao, 0, 40),
        $fatura->valor_total,
        $valorPendente,
        $itensPagos,
        $totalItens
    );
}
