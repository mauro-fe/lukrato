<?php

/**
 * Debug: Verificar faturas e itens do usuário 23
 */

require_once __DIR__ . '/../bootstrap.php';

use Application\Models\Fatura;
use Application\Services\FaturaService;

echo "=== DEBUG: Faturas do Usuario 23 ===" . PHP_EOL . PHP_EOL;

// Testar FaturaService.listar() - isso é o que a API retorna
echo "=== Testando FaturaService.listar() ===" . PHP_EOL;
$service = new FaturaService();
$resultado = $service->listar(23);

echo "Resultado da API (formatarFaturaListagem):" . PHP_EOL;
foreach ($resultado as $fat) {
    echo "  Fatura ID: {$fat['id']}" . PHP_EOL;
    echo "    descricao: {$fat['descricao']}" . PHP_EOL;
    echo "    valor_total: R$ " . number_format($fat['valor_total'], 2, ',', '.') . PHP_EOL;
    echo "    parcelas_pendentes: {$fat['parcelas_pendentes']}" . PHP_EOL;
    echo PHP_EOL;
}
