<?php

require_once __DIR__ . '/../bootstrap.php';

use Application\Services\CartaoFaturaService;
use Application\Services\CartaoCreditoService;

$userId = 22;

echo "🧪 Testando serviços de alertas de cartões...\n\n";

try {
    $cartaoService = new CartaoCreditoService();
    $faturaService = new CartaoFaturaService();

    echo "1️⃣ Verificando vencimentos próximos...\n";
    $alertasVencimento = $faturaService->verificarVencimentosProximos($userId);
    echo "   ✅ Encontrados: " . count($alertasVencimento) . " alertas\n";
    print_r($alertasVencimento);

    echo "\n2️⃣ Verificando limites baixos...\n";
    $alertasLimite = $cartaoService->verificarLimitesBaixos($userId);
    echo "   ✅ Encontrados: " . count($alertasLimite) . " alertas\n";
    print_r($alertasLimite);

    echo "\n✅ Testes concluídos com sucesso!\n";
} catch (\Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
