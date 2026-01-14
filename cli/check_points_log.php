<?php
require_once __DIR__ . '/../bootstrap.php';

use Application\Models\PointsLog;

$userId = $argv[1] ?? 1;

echo "📊 Points Log para usuário #{$userId}\n\n";

$count = PointsLog::where('user_id', $userId)->count();
echo "Total de registros: {$count}\n\n";

if ($count > 0) {
    echo "Últimas 10 atividades:\n";
    $logs = PointsLog::where('user_id', $userId)
        ->orderBy('created_at', 'desc')
        ->limit(10)
        ->get();

    foreach ($logs as $log) {
        $date = $log->created_at ? $log->created_at->format('d/m/Y H:i') : 'N/A';
        echo "  - [{$date}] {$log->action}: +{$log->points} pts\n";
    }
} else {
    echo "❌ Nenhum registro encontrado na tabela points_log.\n";
    echo "\n💡 Isso significa que as ações do usuário não estão sendo registradas no log de pontos.\n";
    echo "   O histórico só vai aparecer quando o sistema registrar atividades.\n";
}
