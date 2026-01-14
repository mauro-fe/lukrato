<?php

/**
 * CLI: Check and Unlock Achievements for User
 * 
 * Verifica e desbloqueia todas as conquistas disponíveis para um usuário
 */

require_once __DIR__ . '/../bootstrap.php';

use Application\Services\AchievementService;
use Application\Models\Usuario;
use Application\Models\UserProgress;
use Application\Models\UserAchievement;
use Application\Models\Achievement;

$userId = $argv[1] ?? 1;

echo "🎮 ======================================\n";
echo "   CHECK & UNLOCK ACHIEVEMENTS\n";
echo "======================================\n\n";

$user = Usuario::find($userId);
if (!$user) {
    echo "❌ Usuário #{$userId} não encontrado!\n";
    exit(1);
}

echo "👤 Usuário: {$user->nome} (ID: {$user->id})\n";
echo "📊 Plano: " . ($user->isPro() ? 'PRO ⭐' : 'FREE') . "\n\n";

// Verificar progresso
$progress = UserProgress::where('user_id', $userId)->first();
if ($progress) {
    echo "📈 PROGRESSO ATUAL:\n";
    echo "   - Nível: {$progress->current_level}\n";
    echo "   - Pontos: {$progress->total_points}\n";
    echo "   - Dias Ativos: {$progress->current_streak}\n";
    echo "   - Melhor Sequência: {$progress->best_streak}\n\n";
}

$achievementService = new AchievementService();

echo "🔍 Verificando conquistas...\n\n";

$unlockedNow = $achievementService->checkAndUnlockAchievements($userId);

if (count($unlockedNow) > 0) {
    echo "✅ CONQUISTAS DESBLOQUEADAS AGORA:\n";
    foreach ($unlockedNow as $achievement) {
        echo "   🏆 {$achievement['name']} ({$achievement['code']}) - +{$achievement['points_reward']} pts\n";
    }
} else {
    echo "📝 Nenhuma nova conquista desbloqueada.\n";
}

// Listar todas as conquistas do usuário
echo "\n======================================\n";
echo "📋 TODAS AS CONQUISTAS:\n";
echo "======================================\n\n";

$allAchievements = $achievementService->getUserAchievements($userId);

$unlockedCount = 0;
$totalCount = count($allAchievements);

$categories = [
    'streak' => ['name' => '🔥 DIAS ATIVOS', 'items' => []],
    'financial' => ['name' => '💰 ECONOMIA', 'items' => []],
    'level' => ['name' => '⭐ NÍVEIS', 'items' => []],
    'premium' => ['name' => '👑 PREMIUM', 'items' => []],
    'cards' => ['name' => '💳 CARTÕES', 'items' => []],
    'milestone' => ['name' => '🎂 TEMPO DE USO', 'items' => []],
    'special' => ['name' => '🎯 ESPECIAIS', 'items' => []],
    'usage' => ['name' => '📊 USO GERAL', 'items' => []],
];

foreach ($allAchievements as $achievement) {
    $cat = $achievement['category'] ?? 'usage';
    $status = $achievement['unlocked'] ? '✅' : '⬜';
    $pro = $achievement['is_pro_only'] ? ' [PRO]' : '';

    if ($achievement['unlocked']) {
        $unlockedCount++;
    }

    if (isset($categories[$cat])) {
        $categories[$cat]['items'][] = "{$status} {$achievement['name']}{$pro}";
    }
}

foreach ($categories as $cat) {
    if (count($cat['items']) > 0) {
        echo "{$cat['name']}\n";
        foreach ($cat['items'] as $item) {
            echo "   {$item}\n";
        }
        echo "\n";
    }
}

echo "======================================\n";
echo "📊 RESUMO: {$unlockedCount}/{$totalCount} conquistas desbloqueadas\n";
echo "======================================\n";
