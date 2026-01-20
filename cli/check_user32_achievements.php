<?php

/**
 * Verificar conquistas desbloqueadas do usuário 32
 */

require_once __DIR__ . '/../bootstrap.php';

use Application\Models\UserAchievement;
use Application\Models\Achievement;

$userId = 32;

echo "\n🏆 CONQUISTAS DESBLOQUEADAS - USER #{$userId}\n";
echo "══════════════════════════════════════════════════════════\n\n";

$userAchievements = UserAchievement::where('user_id', $userId)
    ->with('achievement')
    ->orderBy('unlocked_at', 'desc')
    ->get();

if ($userAchievements->isEmpty()) {
    echo "❌ Nenhuma conquista desbloqueada\n\n";
} else {
    echo "Total: {$userAchievements->count()} conquistas\n\n";

    $totalPointsFromAchievements = 0;

    foreach ($userAchievements as $ua) {
        $achievement = $ua->achievement;
        if (!$achievement) continue;

        $totalPointsFromAchievements += $achievement->points_reward;

        echo "┌─────────────────────────────────────────────────────\n";
        echo "│ {$achievement->icon} {$achievement->name}\n";
        echo "│ Code: {$achievement->code}\n";
        echo "│ Descrição: {$achievement->description}\n";
        echo "│ 💰 Pontos de recompensa: {$achievement->points_reward}\n";
        echo "│ 📅 Desbloqueada em: {$ua->unlocked_at->format('Y-m-d H:i:s')}\n";
        echo "│ 👁️  Notificação vista: " . ($ua->notification_seen ? 'Sim' : 'Não') . "\n";
        echo "└─────────────────────────────────────────────────────\n\n";
    }

    echo "══════════════════════════════════════════════════════════\n";
    echo "💰 TOTAL DE PONTOS DAS CONQUISTAS: {$totalPointsFromAchievements}\n";
    echo "══════════════════════════════════════════════════════════\n\n";
}
