<?php

/**
 * Verificar e corrigir divergências de pontos entre points_log e user_progress
 * 
 * BUG DETECTADO: Conquistas estavam adicionando pontos diretamente no user_progress
 * sem registrar no points_log, causando divergências.
 */

require_once __DIR__ . '/../bootstrap.php';

use Application\Models\UserProgress;
use Application\Models\PointsLog;
use Application\Models\Usuario;

echo "\n🔍 ══════════════════════════════════════════════════════════\n";
echo "   VERIFICAÇÃO DE INTEGRIDADE - PONTOS DE GAMIFICAÇÃO\n";
echo "══════════════════════════════════════════════════════════\n\n";

$allProgress = UserProgress::all();

echo "📊 Total de usuários com progresso: {$allProgress->count()}\n\n";

$divergencias = [];
$totalDivergencias = 0;

foreach ($allProgress as $progress) {
    $userId = $progress->user_id;

    // Calcular total de pontos nos logs
    $totalPontosLog = PointsLog::where('user_id', $userId)->sum('points');

    // Comparar com o progresso
    $totalPontosProgress = $progress->total_points;

    if ($totalPontosLog != $totalPontosProgress) {
        $diferenca = $totalPontosProgress - $totalPontosLog;
        $divergencias[] = [
            'user_id' => $userId,
            'progress_points' => $totalPontosProgress,
            'log_points' => $totalPontosLog,
            'diff' => $diferenca,
        ];
        $totalDivergencias += abs($diferenca);
    }
}

if (empty($divergencias)) {
    echo "✅ PERFEITO! Nenhuma divergência encontrada!\n";
    echo "   Todos os pontos estão corretamente registrados nos logs.\n\n";
    exit(0);
}

echo "⚠️  DIVERGÊNCIAS ENCONTRADAS: " . count($divergencias) . " usuários\n";
echo "══════════════════════════════════════════════════════════\n\n";

foreach ($divergencias as $div) {
    $user = Usuario::find($div['user_id']);
    $userName = $user ? $user->nome : "User #{$div['user_id']}";

    echo "👤 {$userName} (ID: {$div['user_id']})\n";
    echo "   Progress: {$div['progress_points']} pts\n";
    echo "   Logs:     {$div['log_points']} pts\n";
    echo "   Diferença: " . ($div['diff'] > 0 ? '+' : '') . "{$div['diff']} pts\n";
    echo "\n";
}

echo "══════════════════════════════════════════════════════════\n";
echo "💰 Total de pontos em divergência: " . number_format($totalDivergencias, 0, ',', '.') . "\n";
echo "══════════════════════════════════════════════════════════\n\n";

echo "🛠️  OPÇÕES DE CORREÇÃO:\n";
echo "─────────────────────────────────────────────────────────\n";
echo "1. Recalcular todos os usuários com base nos logs (RECOMENDADO)\n";
echo "   - user_progress.total_points = SUM(points_log.points)\n";
echo "   - Mantém apenas pontos que estão registrados\n";
echo "\n";
echo "2. Adicionar logs retroativos para pontos perdidos\n";
echo "   - Criar entradas no points_log para justificar os pontos\n";
echo "   - Útil se os pontos foram dados corretamente mas não logados\n";
echo "\n";

$handle = fopen("php://stdin", "r");
echo "Deseja aplicar a correção #1 (recalcular com base nos logs)? [s/N]: ";
$resposta = strtolower(trim(fgets($handle)));

if ($resposta !== 's' && $resposta !== 'sim') {
    echo "\n❌ Operação cancelada.\n\n";
    exit(0);
}

echo "\n🔧 Aplicando correção...\n";
echo "─────────────────────────────────────────────────────────\n\n";

$corrigidos = 0;
$erros = 0;

foreach ($divergencias as $div) {
    try {
        $progress = UserProgress::where('user_id', $div['user_id'])->first();
        if (!$progress) {
            echo "⚠️  User {$div['user_id']}: Progresso não encontrado\n";
            $erros++;
            continue;
        }

        // Recalcular baseado nos logs
        $totalCorreto = PointsLog::where('user_id', $div['user_id'])->sum('points');

        $pontoAntigo = $progress->total_points;
        $progress->total_points = $totalCorreto;
        $progress->save();

        // Recalcular nível com base nos novos pontos
        $gamificationService = new \Application\Services\GamificationService();
        $gamificationService->recalculateLevel($div['user_id']);

        echo "✅ User {$div['user_id']}: {$pontoAntigo} → {$totalCorreto} pts\n";
        $corrigidos++;
    } catch (Exception $e) {
        echo "❌ User {$div['user_id']}: Erro - " . $e->getMessage() . "\n";
        $erros++;
    }
}

echo "\n══════════════════════════════════════════════════════════\n";
echo "✅ Usuários corrigidos: {$corrigidos}\n";
if ($erros > 0) {
    echo "❌ Erros: {$erros}\n";
}
echo "══════════════════════════════════════════════════════════\n\n";

echo "🎉 Correção concluída!\n\n";
