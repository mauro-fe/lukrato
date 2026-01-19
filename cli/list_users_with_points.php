<?php

require_once __DIR__ . '/../bootstrap.php';

use Application\Models\Usuario;
use Application\Models\UserProgress;
use Application\Models\PointsLog;

// Encontrar usuários recentes com pontos
echo "🔍 Buscando usuários com pontuação recente...\n\n";

$usersWithPoints = UserProgress::where('total_points', '>', 0)
    ->orderByDesc('updated_at')
    ->limit(10)
    ->with('user')
    ->get();

foreach ($usersWithPoints as $progress) {
    $user = $progress->user;
    if (!$user) continue;

    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "👤 {$user->nome} (ID: {$user->id})\n";
    echo "📧 {$user->email}\n";
    echo "📊 {$progress->total_points} pontos | Nível {$progress->current_level}\n";
    echo "📅 Criado: {$user->created_at}\n";
    echo "🔄 Atualizado: {$progress->updated_at}\n";

    // Contar logs e conquistas
    $logsCount = PointsLog::where('user_id', $user->id)->count();
    $achievementsCount = \Application\Models\UserAchievement::where('user_id', $user->id)->count();

    echo "📝 {$logsCount} registros de ação | 🏆 {$achievementsCount} conquistas\n";
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Digite o ID do usuário para análise detalhada:\n";
