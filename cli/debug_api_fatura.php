<?php

require __DIR__ . '/../bootstrap.php';

$cartaoId = 27;
$mes = 1;
$ano = 2026;

echo "=== SIMULANDO API GET /api/cartoes/27/fatura?mes=1&ano=2026 ===\n\n";

$service = new \Application\Services\CartaoFaturaService();
$fatura = $service->obterFaturaMes($cartaoId, $mes, $ano);

echo "Resultado da API:\n";
echo json_encode($fatura, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

echo "\n\n=== VERIFICAÇÃO ===\n";
echo "Total retornado pela API: R$ " . number_format($fatura['total'], 2, ',', '.') . "\n";
echo "Quantidade de itens: " . count($fatura['itens']) . "\n";

// Somar manualmente
$somaManual = 0;
$itensPendentes = 0;
foreach ($fatura['itens'] as $item) {
    if (!$item['pago']) {
        $somaManual += $item['valor'];
        $itensPendentes++;
    }
}

echo "Itens pendentes: {$itensPendentes}\n";
echo "Soma manual dos pendentes: R$ " . number_format($somaManual, 2, ',', '.') . "\n";
