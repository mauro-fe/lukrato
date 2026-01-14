<?php
require_once __DIR__ . '/../bootstrap.php';

use Application\Models\UserAchievement;
use Application\Models\Achievement;
use Application\Models\Usuario;

// Buscar usuário Mauro (ID 1)
$user = Usuario::find(1);

if (!$user) {
    echo "Usuário não encontrado!\n";
    exit(1);
}

echo "Usuário: {$user->nome} (ID: {$user->id})\n";
echo "É Pro: " . ($user->isPro() ? 'Sim' : 'Não') . "\n\n";

// Buscar conquistas desbloqueadas
$userAchievements = UserAchievement::where('user_id', $user->id)->get();

echo "Conquistas desbloqueadas ({$userAchievements->count()}):\n";
echo str_repeat('-', 60) . "\n";

foreach ($userAchievements as $ua) {
    $achievement = Achievement::find($ua->achievement_id);
    echo "ID: {$ua->achievement_id} | ";
    echo "Code: " . ($achievement ? $achievement->code : 'N/A') . " | ";
    echo "Name: " . ($achievement ? $achievement->name : 'N/A') . " | ";
    echo "Plan: " . ($achievement ? $achievement->plan_type : 'N/A') . " | ";
    echo "Unlocked: {$ua->unlocked_at}\n";
}

echo "\n" . str_repeat('-', 60) . "\n";
echo "Todas as conquistas disponíveis:\n";

$allAchievements = Achievement::active()->orderBy('sort_order')->get();
foreach ($allAchievements as $a) {
    $unlocked = $userAchievements->where('achievement_id', $a->id)->first();
    $status = $unlocked ? '✓' : '✗';
    echo "{$status} | ID: {$a->id} | {$a->code} | {$a->name} | Plan: {$a->plan_type}\n";
}
