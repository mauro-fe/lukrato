<?php

require_once __DIR__ . '/../bootstrap.php';

use Application\Models\UserAchievement;
use Application\Models\UserProgress;

$userId = 29;

echo "🔄 Resetando conquistas incorretas do usuário ID: {$userId}\n\n";

// IDs das conquistas a remover
$achievementsToRemove = [
    'TOTAL_5_CATEGORIES',
    'POSITIVE_MONTH',
    'SAVER_10',
    'SAVER_20',
    'TOTAL_15_CATEGORIES'
];

// Buscar e remover
foreach ($achievementsToRemove as $code) {
    $achievement = \Application\Models\Achievement::where('code', $code)->first();
    if ($achievement) {
        $userAch = UserAchievement::where('user_id', $userId)
            ->where('achievement_id', $achievement->id)
            ->first();

        if ($userAch) {
            echo "❌ Removendo: {$achievement->name} (-{$achievement->points_reward} pts)\n";
            $userAch->delete();

            // Subtrair pontos
            $progress = UserProgress::where('user_id', $userId)->first();
            if ($progress) {
                $progress->total_points -= $achievement->points_reward;
                $progress->save();
            }
        }
    }
}

echo "\n✅ Conquistas resetadas!\n\n";

// Mostrar status final
$progress = UserProgress::where('user_id', $userId)->first();
if ($progress) {
    echo "📊 Status final:\n";
    echo "   Total de pontos: {$progress->total_points}\n";
    echo "   Nível: {$progress->current_level}\n";
}
