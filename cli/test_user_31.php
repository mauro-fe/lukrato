<?php

require_once __DIR__ . '/../bootstrap.php';

use Application\Services\AchievementService;
use Application\Models\Usuario;

echo "🧪 TESTANDO COM USUÁRIO NOVO (ID 31)\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$userId = 31;

// Verificar se usuário existe
$user = Usuario::find($userId);
if (!$user) {
    echo "❌ Usuário ID 31 não existe!\n";
    exit;
}

echo "✅ Usuário encontrado: {$user->nome}\n";
echo "💎 isPro: " . ($user->isPro() ? 'SIM' : 'NÃO') . "\n\n";

// Verificar conquistas desbloqueadas
$userAchievements = \Application\Models\UserAchievement::where('user_id', $userId)->count();
echo "🏆 Conquistas desbloqueadas: {$userAchievements}\n\n";

// Testar o serviço
echo "📊 Testando AchievementService::getUserAchievements()...\n\n";

try {
    $service = new AchievementService();
    $achievements = $service->getUserAchievements($userId, '2026-01');

    echo "✅ Sucesso!\n";
    echo "📦 Total retornado: " . count($achievements) . "\n";

    if (count($achievements) > 0) {
        echo "\n🎯 Primeiras 3 conquistas:\n";
        foreach (array_slice($achievements, 0, 3) as $a) {
            echo sprintf(
                "  [%-20s] %s - %s\n",
                $a['code'],
                $a['name'],
                $a['unlocked'] ? '✅ Desbloqueada' : '❌ Bloqueada'
            );
        }
    } else {
        echo "\n⚠️ NENHUMA CONQUISTA RETORNADA!\n";
    }
} catch (\Exception $e) {
    echo "\n❌ ERRO ENCONTRADO:\n";
    echo "Mensagem: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\nStack Trace:\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n✅ Teste concluído!\n";
