<?php

require_once __DIR__ . '/../bootstrap.php';

use Application\Models\Usuario;
use Application\Services\AchievementService;

echo "🧪 TESTE COMPLETO DA API DE CONQUISTAS\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$userId = 1;

// Instanciar serviço
$service = new AchievementService();

echo "📊 Testando getUserAchievements()...\n\n";

try {
    $result = $service->getUserAchievements($userId);

    echo "✅ Resultado obtido com sucesso!\n";
    echo "📦 Total de conquistas retornadas: " . count($result) . "\n\n";

    if (count($result) > 0) {
        echo "🎯 Primeiras 5 conquistas:\n";

        foreach (array_slice($result, 0, 5) as $index => $achievement) {
            $status = $achievement['unlocked'] ? '✅ DESBLOQUEADA' : '❌ Bloqueada';
            echo sprintf(
                "\n%d. %s [%s]\n",
                $index + 1,
                $achievement['name'],
                $achievement['code']
            );
            echo "   Status: $status\n";
            echo "   Pontos: {$achievement['points_reward']}\n";
            echo "   Tipo: {$achievement['plan_type']}\n";
            echo "   Ícone: {$achievement['icon']}\n";
        }
    } else {
        echo "\n❌ PROBLEMA: Nenhuma conquista retornada!\n";
    }

    // JSON para debug (como a API retorna)
    echo "\n\n📄 JSON retornado (primeiras 3):\n";
    echo json_encode(array_slice($result, 0, 3), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} catch (\Exception $e) {
    echo "\n❌ ERRO: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n\n✅ Teste concluído!\n";
