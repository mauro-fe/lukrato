<?php

require dirname(__DIR__) . '/bootstrap.php';
require BASE_PATH . '/config/config.php';

use Application\Services\ContaService;

echo "=== TESTE API CONTAS ===\n\n";

try {
    $service = new ContaService();
    
    echo "✓ ContaService instanciado com sucesso!\n\n";
    
    // Testar listarContas com userId 1
    $userId = 1;
    echo "Listando contas para user_id=$userId...\n";
    
    $contas = $service->listarContas(
        userId: $userId,
        arquivadas: false,
        apenasAtivas: true,
        comSaldos: false,
        mes: date('Y-m')
    );
    
    echo "✓ Contas retornadas: " . count($contas) . "\n";
    
    if (!empty($contas)) {
        echo "\nPrimeira conta:\n";
        print_r($contas[0]);
    }
    
} catch (\Throwable $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}
