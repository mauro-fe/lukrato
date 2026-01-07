<?php

require __DIR__ . '/../bootstrap.php';

echo "=== TESTANDO CORREÇÃO API FATURAS ===\n\n";

$service = new \Application\Services\FaturaService();
$faturas = $service->listar(1);

echo "Total de faturas retornadas: " . count($faturas) . "\n\n";

foreach ($faturas as $fatura) {
    echo sprintf(
        "ID: %d | %s | Total a Pagar: R$ %.2f | Status: %s | Pendentes: %d\n",
        $fatura['id'],
        $fatura['descricao'],
        $fatura['valor_total'],
        $fatura['status'],
        $fatura['parcelas_pendentes']
    );
}

echo "\n=== DETALHES FATURA JANEIRO 2026 ===\n";
$faturaJan = array_filter($faturas, fn($f) => $f['descricao'] === 'Fatura 1/2026');
$faturaJan = array_values($faturaJan)[0] ?? null;

if ($faturaJan) {
    echo json_encode($faturaJan, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
