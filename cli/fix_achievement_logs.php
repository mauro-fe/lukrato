<?php

/**
 * Adicionar logs retroativos para pontos de conquistas
 * 
 * Este script corrige o bug onde conquistas davam pontos mas não
 * registravam no points_log, causando divergências.
 */

require_once __DIR__ . '/../bootstrap.php';

use Application\Models\UserAchievement;
use Application\Models\PointsLog;
use Application\Models\UserProgress;
use Application\Models\Usuario;

echo "\n🔧 ══════════════════════════════════════════════════════════\n";
echo "   CORREÇÃO: LOGS RETROATIVOS DE CONQUISTAS\n";
echo "══════════════════════════════════════════════════════════\n\n";

// Buscar todas as conquistas desbloqueadas
$userAchievements = UserAchievement::with('achievement')
    ->orderBy('unlocked_at', 'asc')
    ->get();

echo "🏆 Total de conquistas desbloqueadas: {$userAchievements->count()}\n\n";

$logsAdicionados = 0;
$logsExistentes = 0;
$erros = 0;

foreach ($userAchievements as $ua) {
    $achievement = $ua->achievement;
    if (!$achievement || $achievement->points_reward <= 0) {
        continue;
    }

    $userId = $ua->user_id;
    $achievementId = $ua->achievement_id;

    // Verificar se já existe log para esta conquista
    $existingLog = PointsLog::where('user_id', $userId)
        ->where('action', 'achievement_unlock')
        ->where('related_type', 'achievement')
        ->where('related_id', $achievementId)
        ->first();

    if ($existingLog) {
        $logsExistentes++;
        continue;
    }

    // Criar log retroativo
    try {
        PointsLog::create([
            'user_id' => $userId,
            'action' => 'achievement_unlock',
            'points' => $achievement->points_reward,
            'description' => "Conquista desbloqueada: {$achievement->name}",
            'metadata' => [
                'achievement_code' => $achievement->code,
                'achievement_id' => $achievement->id,
                'retroactive' => true,
                'fix_date' => date('Y-m-d H:i:s'),
            ],
            'related_id' => $achievementId,
            'related_type' => 'achievement',
            'created_at' => $ua->unlocked_at, // Data original do desbloqueio
            'updated_at' => now(),
        ]);

        $user = Usuario::find($userId);
        $userName = $user ? $user->nome : "User #{$userId}";

        echo "✅ {$userName}: '{$achievement->name}' (+{$achievement->points_reward} pts)\n";
        $logsAdicionados++;
    } catch (Exception $e) {
        echo "❌ Erro ao criar log para user {$userId}, achievement {$achievementId}: {$e->getMessage()}\n";
        $erros++;
    }
}

echo "\n══════════════════════════════════════════════════════════\n";
echo "📊 RESULTADO:\n";
echo "   ✅ Logs adicionados: {$logsAdicionados}\n";
echo "   ℹ️  Logs já existentes: {$logsExistentes}\n";
if ($erros > 0) {
    echo "   ❌ Erros: {$erros}\n";
}
echo "══════════════════════════════════════════════════════════\n\n";

// Verificar se as divergências foram corrigidas
echo "🔍 Verificando integridade após correção...\n\n";

$allProgress = UserProgress::all();
$divergencias = 0;

foreach ($allProgress as $progress) {
    $totalPontosLog = PointsLog::where('user_id', $progress->user_id)->sum('points');
    $totalPontosProgress = $progress->total_points;

    if ($totalPontosLog != $totalPontosProgress) {
        $divergencias++;
        $user = Usuario::find($progress->user_id);
        $userName = $user ? $user->nome : "User #{$progress->user_id}";
        $diff = $totalPontosProgress - $totalPontosLog;

        echo "⚠️  {$userName}: Progress={$totalPontosProgress}, Log={$totalPontosLog}, Diff=" . ($diff > 0 ? '+' : '') . "{$diff}\n";
    }
}

if ($divergencias == 0) {
    echo "✅ PERFEITO! Todas as divergências foram corrigidas!\n";
} else {
    echo "\n⚠️  Ainda há {$divergencias} divergências. Pode ser necessário investigação adicional.\n";
}

echo "\n🎉 Correção concluída!\n\n";
