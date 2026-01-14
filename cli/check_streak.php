<?php
require_once __DIR__ . '/../bootstrap.php';

use Application\Models\UserProgress;
use Application\Services\StreakService;

$userId = 1;

// Verificar dados de progresso
$progress = UserProgress::where('user_id', $userId)->first();

echo "=== Dados de Progresso do Usuário ===\n";
if ($progress) {
    echo "Total Points: {$progress->total_points}\n";
    echo "Current Level: {$progress->current_level}\n";
    echo "Current Streak: {$progress->current_streak}\n";
    echo "Best Streak: {$progress->best_streak}\n";
    echo "Last Activity Date: {$progress->last_activity_date}\n";
    echo "Points to Next Level: {$progress->points_to_next_level}\n";
    echo "Progress Percentage: {$progress->progress_percentage}\n";
} else {
    echo "Nenhum progresso encontrado!\n";
}

echo "\n=== Verificando Streak Service ===\n";
$streakService = new StreakService();
$streakInfo = $streakService->getStreakInfo($userId);
print_r($streakInfo);
