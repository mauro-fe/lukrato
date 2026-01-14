<?php
require_once __DIR__ . '/../bootstrap.php';

use Application\Services\AchievementService;
use Application\Models\Usuario;

// Buscar usuário
$userId = 1;
$user = Usuario::find($userId);

if (!$user) {
    echo "Usuário não encontrado!\n";
    exit(1);
}

echo "Verificando conquistas para: {$user->nome} (ID: {$user->id})\n";
echo "É Pro: " . ($user->isPro() ? 'Sim' : 'Não') . "\n\n";

$achievementService = new AchievementService();

// Verificar e desbloquear conquistas automaticamente
echo "Verificando conquistas automaticamente...\n";
$newAchievements = $achievementService->checkAndUnlockAchievements($userId);

if (count($newAchievements) > 0) {
    echo "\nConquistas desbloqueadas agora:\n";
    foreach ($newAchievements as $a) {
        echo "  ✓ {$a['name']} ({$a['code']}) - {$a['points_reward']} pontos\n";
    }
} else {
    echo "\nNenhuma nova conquista desbloqueada.\n";
}

// Verificar novamente
echo "\n" . str_repeat('-', 60) . "\n";
echo "Verificando status atual das conquistas...\n\n";

$achievements = $achievementService->getUserAchievements($userId);
$unlocked = array_filter($achievements, fn($a) => $a['unlocked'] || $a['unlocked_ever']);

echo "Conquistas desbloqueadas: " . count($unlocked) . "/" . count($achievements) . "\n\n";

foreach ($achievements as $a) {
    $status = ($a['unlocked'] || $a['unlocked_ever']) ? '✓' : '✗';
    $canUnlock = $a['can_unlock'] ? ' (pode desbloquear!)' : '';
    echo "{$status} {$a['name']} | {$a['plan_type']}{$canUnlock}\n";
}
