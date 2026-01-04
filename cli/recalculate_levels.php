#!/usr/bin/env php
<?php
/**
 * Script para recalcular níveis de gamificação
 * Corrige inconsistências entre pontos e níveis
 */

require_once dirname(__DIR__) . '/bootstrap.php';

use Application\Models\UserProgress;
use Illuminate\Database\Capsule\Manager as DB;

echo "🔄 Recalculando níveis de gamificação...\n";
echo str_repeat("=", 60) . "\n\n";

$thresholds = [
    1 => 0,
    2 => 300,
    3 => 500,
    4 => 700,
    5 => 1000,
    6 => 1500,
    7 => 2200,
    8 => 3000,
];

try {
    $progressRecords = UserProgress::all();

    echo "Total de registros encontrados: " . $progressRecords->count() . "\n\n";

    foreach ($progressRecords as $progress) {
        $oldLevel = $progress->current_level;
        $points = $progress->total_points;

        // Calcular nível correto baseado nos pontos
        $newLevel = 1;
        foreach (array_reverse($thresholds, true) as $level => $threshold) {
            if ($points >= $threshold) {
                $newLevel = $level;
                break;
            }
        }

        // Calcular pontos para próximo nível
        $nextLevelThreshold = $thresholds[$newLevel + 1] ?? $thresholds[8];
        $pointsToNext = $nextLevelThreshold - $points;

        // Calcular porcentagem de progresso no nível atual
        $currentLevelThreshold = $thresholds[$newLevel];
        $nextThreshold = $thresholds[$newLevel + 1] ?? $currentLevelThreshold;
        $pointsInLevel = $points - $currentLevelThreshold;
        $pointsForLevel = $nextThreshold - $currentLevelThreshold;
        $progressPercentage = $pointsForLevel > 0
            ? min(100, ($pointsInLevel / $pointsForLevel) * 100)
            : 100;

        // Atualizar registro
        $progress->current_level = $newLevel;
        $progress->points_to_next_level = max(0, $pointsToNext);
        $progress->progress_percentage = round($progressPercentage, 1);
        $progress->save();

        $status = $oldLevel !== $newLevel ? "✓ CORRIGIDO" : "✓ OK";

        echo sprintf(
            "%s User %d (%s): %d pontos → Nível %d → %d%% (era nível %d)\n",
            $status,
            $progress->user_id,
            $progress->user->nome ?? 'Desconhecido',
            $points,
            $newLevel,
            round($progressPercentage, 1),
            $oldLevel
        );
    }

    echo "\n" . str_repeat("=", 60) . "\n";
    echo "✅ Recálculo concluído com sucesso!\n";
} catch (\Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
