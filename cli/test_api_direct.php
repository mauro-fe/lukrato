<?php

require_once __DIR__ . '/../bootstrap.php';

echo "🧪 TESTE DIRETO DA API\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Simular sessão
$_SESSION['user_id'] = 1;

use Application\Controllers\Api\GamificationController;

try {
    echo "📡 Criando controller...\n";
    $controller = new GamificationController();

    echo "📡 Chamando getAchievements()...\n";
    $result = $controller->getAchievements();

    echo "✅ Resultado obtido!\n\n";
    echo "📄 Response:\n";
    print_r($result);
} catch (\Exception $e) {
    echo "\n❌ ERRO CAPTURADO:\n";
    echo "Mensagem: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\nStack Trace:\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n✅ Teste concluído!\n";
