#!/usr/bin/env php
<?php
/**
 * Script para corrigir pontos de gamificação negativos
 * Atualiza o campo points_to_next_level para os valores corretos
 */

require_once dirname(__DIR__) . '/bootstrap.php';

use Application\Models\UserProgress;
use Illuminate\Database\Capsule\Manager as DB;

echo "🔧 Corrigindo pontos de gamificação...\n";
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

    $fixed = 0;

    foreach ($progressRecords as $progress) {
        $points = $progress->total_points;
        $currentLevel = $progress->current_level;
        $oldPointsToNext = $progress->points_to_next_level;

        // Calcular pontos corretos para próximo nível
        $nextLevelThreshold = $thresholds[$currentLevel + 1] ?? null;
        $correctPointsToNext = $nextLevelThreshold ? ($nextLevelThreshold - $points) : 0;

        // Se está incorreto, corrigir
        if ($oldPointsToNext != $correctPointsToNext) {
            $progress->points_to_next_level = $correctPointsToNext;
            $progress->save();

            echo "✅ User ID {$progress->user_id}:\n";
            echo "   Nível: {$currentLevel}\n";
            echo "   Pontos Totais: {$points}\n";
            echo "   Pontos para próximo (ANTES): {$oldPointsToNext}\n";
            echo "   Pontos para próximo (AGORA): {$correctPointsToNext}\n\n";

            $fixed++;
        }
    }

    echo str_repeat("=", 60) . "\n";
    echo "✅ Correção concluída!\n";
    echo "📊 Total de registros corrigidos: {$fixed}\n";
    echo "📊 Total de registros OK: " . ($progressRecords->count() - $fixed) . "\n";
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    exit(1);
}
