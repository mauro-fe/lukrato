<?php

require_once __DIR__ . '/../bootstrap.php';

use Application\Models\Usuario;
use Application\Models\UserProgress;
use Application\Models\UserAchievement;
use Application\Models\PointsLog;

// Encontrar último usuário criado
$lastUser = Usuario::orderByDesc('id')->first();

if (!$lastUser) {
    echo "❌ Nenhum usuário encontrado\n";
    exit;
}

echo "🔍 Analisando usuário: {$lastUser->nome} (ID: {$lastUser->id})\n";
echo "📧 Email: {$lastUser->email}\n";
echo "📅 Criado em: {$lastUser->created_at}\n\n";

// Verificar progresso
$progress = UserProgress::where('user_id', $lastUser->id)->first();

if ($progress) {
    echo "📊 PROGRESSO:\n";
    echo "   Total de pontos: {$progress->total_points}\n";
    echo "   Nível atual: {$progress->current_level}\n";
    echo "   Streak: {$progress->current_streak}\n\n";
}

// Listar logs de pontos
echo "📝 HISTÓRICO DE PONTOS:\n";
$logs = PointsLog::where('user_id', $lastUser->id)
    ->orderBy('created_at')
    ->get();

$totalFromLogs = 0;
foreach ($logs as $log) {
    echo "   [{$log->created_at}] {$log->action}: +{$log->points} pts - {$log->description}\n";
    $totalFromLogs += $log->points;
}
echo "   TOTAL dos logs: {$totalFromLogs} pontos\n\n";

// Listar conquistas desbloqueadas
echo "🏆 CONQUISTAS DESBLOQUEADAS:\n";
$achievements = UserAchievement::where('user_id', $lastUser->id)
    ->with('achievement')
    ->orderBy('unlocked_at')
    ->get();

$totalFromAchievements = 0;
foreach ($achievements as $ua) {
    $ach = $ua->achievement;
    echo "   [{$ua->unlocked_at}] {$ach->name} (+{$ach->points_reward} pts)\n";
    echo "      Code: {$ach->code}\n";
    $totalFromAchievements += $ach->points_reward;
}
echo "   TOTAL das conquistas: {$totalFromAchievements} pontos\n\n";

// Cálculo esperado
$expectedTotal = $totalFromLogs + $totalFromAchievements;
echo "💡 ANÁLISE:\n";
echo "   Pontos das ações: {$totalFromLogs}\n";
echo "   Pontos das conquistas: {$totalFromAchievements}\n";
echo "   Total esperado: {$expectedTotal}\n";
echo "   Total no banco: " . ($progress->total_points ?? 0) . "\n";

if ($progress && $expectedTotal != $progress->total_points) {
    echo "   ⚠️ DIFERENÇA: " . ($progress->total_points - $expectedTotal) . " pontos\n";
}
