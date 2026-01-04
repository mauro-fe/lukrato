<?php
require_once __DIR__ . '/../bootstrap.php';

use Application\Services\CartaoFaturaService;
use Application\Services\CartaoCreditoService;

echo "Testando alertas...\n\n";

$userId = 1;

echo "1. Testando verificarVencimentosProximos...\n";
try {
    $faturaService = new CartaoFaturaService();
    $vencimentos = $faturaService->verificarVencimentosProximos($userId, 7);
    echo "   ✅ Vencimentos: " . count($vencimentos) . " alertas\n";
    print_r($vencimentos);
} catch (Exception $e) {
    echo "   ❌ Erro: " . $e->getMessage() . "\n";
    echo "   Stack: " . $e->getTraceAsString() . "\n";
}

echo "\n2. Testando verificarLimitesBaixos...\n";
try {
    $cartaoService = new CartaoCreditoService();
    $limites = $cartaoService->verificarLimitesBaixos($userId);
    echo "   ✅ Limites baixos: " . count($limites) . " alertas\n";
    print_r($limites);
} catch (Exception $e) {
    echo "   ❌ Erro: " . $e->getMessage() . "\n";
    echo "   Stack: " . $e->getTraceAsString() . "\n";
}

echo "\n✅ Teste concluído!\n";
