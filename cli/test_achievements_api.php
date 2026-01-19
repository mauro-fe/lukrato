<?php

require_once __DIR__ . '/../bootstrap.php';

use Application\Lib\Auth;
use Application\Models\Achievement;
use Application\Models\UserAchievement;

// Simular requisição
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/api/gamification/achievements';

// Mock de sessão
$_SESSION['user_id'] = 1;

echo "🧪 TESTE DE API DE CONQUISTAS\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Buscar conquistas
$achievements = Achievement::all();
echo "📊 Total de conquistas no banco: " . $achievements->count() . "\n\n";

if ($achievements->isEmpty()) {
    echo "❌ PROBLEMA: Não há conquistas ativas no banco!\n";
    echo "   Execute: php cli/seed_expanded_achievements.php\n";
    exit;
}

// Buscar conquistas do usuário
$userId = 1;
$userAchievements = UserAchievement::where('user_id', $userId)->count();
echo "🏆 Conquistas desbloqueadas pelo usuário ID {$userId}: {$userAchievements}\n\n";

// Listar primeiras 6
echo "📋 PRIMEIRAS 6 CONQUISTAS:\n";
foreach ($achievements->take(6) as $ach) {
    $unlocked = UserAchievement::where('user_id', $userId)
        ->where('achievement_id', $ach->id)
        ->exists();

    $status = $unlocked ? '✅' : '🔒';
    echo "   {$status} {$ach->icon} {$ach->name} ({$ach->code})\n";
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "✅ Teste concluído!\n";
