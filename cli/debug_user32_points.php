<?php

/**
 * Debug dos pontos do usuário 32
 * Verificar possíveis duplicações ou bugs na gamificação
 */

require_once __DIR__ . '/../bootstrap.php';

use Application\Models\PointsLog;
use Application\Models\UserProgress;
use Application\Models\Lancamento;
use Application\Models\Usuario;

$userId = 32;

echo "\n🔍 ======================================\n";
echo "   ANÁLISE DE GAMIFICAÇÃO - USER #{$userId}\n";
echo "======================================\n\n";

// 1. Buscar usuário
$user = Usuario::find($userId);
if (!$user) {
    echo "❌ Usuário não encontrado!\n";
    exit(1);
}

echo "👤 Usuário: {$user->nome} ({$user->email})\n";
echo "💎 Plano: " . ($user->isPro() ? "PRO" : "FREE") . "\n\n";

// 2. Buscar progresso
$progress = UserProgress::where('user_id', $userId)->first();
if ($progress) {
    echo "📊 PROGRESSO ATUAL:\n";
    echo "   • Total de pontos: {$progress->total_points}\n";
    echo "   • Nível atual: {$progress->current_level}\n";
    echo "   • Pontos para próximo nível: {$progress->points_to_next_level}\n";
    echo "   • Streak atual: {$progress->current_streak}\n";
    echo "   • Melhor streak: {$progress->best_streak}\n\n";
} else {
    echo "⚠️  Nenhum progresso encontrado\n\n";
}

// 3. Buscar todos os logs de pontos
$logs = PointsLog::where('user_id', $userId)
    ->orderBy('created_at', 'desc')
    ->get();

echo "📜 HISTÓRICO DE PONTOS ({$logs->count()} registros):\n";
echo "─────────────────────────────────────────────────────────────\n";

$totalPontos = 0;
$acoesPorTipo = [];

foreach ($logs as $log) {
    $totalPontos += $log->points;

    // Contar ações por tipo
    if (!isset($acoesPorTipo[$log->action])) {
        $acoesPorTipo[$log->action] = ['count' => 0, 'points' => 0];
    }
    $acoesPorTipo[$log->action]['count']++;
    $acoesPorTipo[$log->action]['points'] += $log->points;

    $metadata = is_string($log->metadata) ? json_decode($log->metadata, true) : $log->metadata;
    $isPro = $metadata['is_pro'] ?? false;
    $proLabel = $isPro ? ' [PRO]' : '';

    echo sprintf(
        "[%s] %s%s\n   +%d pontos | %s\n   Related: %s #%s\n",
        $log->created_at->format('Y-m-d H:i:s'),
        $log->action,
        $proLabel,
        $log->points,
        $log->description,
        $log->related_type ?? 'N/A',
        $log->related_id ?? 'N/A'
    );

    if ($metadata) {
        echo "   Metadata: " . json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    }
    echo "\n";
}

echo "─────────────────────────────────────────────────────────────\n";
echo "💰 TOTAL DE PONTOS ACUMULADOS: {$totalPontos}\n\n";

// 4. Resumo por tipo de ação
echo "📈 RESUMO POR AÇÃO:\n";
echo "─────────────────────────────────────────────────────────────\n";
foreach ($acoesPorTipo as $action => $data) {
    echo sprintf(
        "   %-25s: %3d vezes | %5d pontos | Média: %.1f\n",
        $action,
        $data['count'],
        $data['points'],
        $data['count'] > 0 ? $data['points'] / $data['count'] : 0
    );
}
echo "\n";

// 5. Verificar duplicações
echo "🔍 VERIFICANDO DUPLICAÇÕES:\n";
echo "─────────────────────────────────────────────────────────────\n";

$duplicatesFound = false;

// Agrupar por related_id + related_type + action
$groupedLogs = $logs->filter(function ($log) {
    return $log->related_id && $log->related_type;
})->groupBy(function ($log) {
    return $log->action . '|' . $log->related_type . '|' . $log->related_id;
});

foreach ($groupedLogs as $key => $group) {
    if ($group->count() > 1) {
        $duplicatesFound = true;
        list($action, $type, $id) = explode('|', $key);
        echo "⚠️  DUPLICAÇÃO ENCONTRADA!\n";
        echo "   Ação: {$action}\n";
        echo "   Tipo: {$type}\n";
        echo "   ID: {$id}\n";
        echo "   Registros duplicados: {$group->count()}\n";
        echo "   Datas:\n";
        foreach ($group as $log) {
            echo "     • {$log->created_at->format('Y-m-d H:i:s')} (+{$log->points} pts)\n";
        }
        echo "\n";
    }
}

if (!$duplicatesFound) {
    echo "✅ Nenhuma duplicação encontrada!\n\n";
}

// 6. Verificar lançamentos
$lancamentos = Lancamento::where('user_id', $userId)
    ->orderBy('created_at', 'desc')
    ->get();

echo "📝 LANÇAMENTOS CRIADOS: {$lancamentos->count()}\n";
echo "─────────────────────────────────────────────────────────────\n";

foreach ($lancamentos as $lanc) {
    $pontoLog = $logs->where('related_type', 'lancamento')
        ->where('related_id', $lanc->id)
        ->first();

    $pontosGanhos = $pontoLog ? "+{$pontoLog->points} pts" : "SEM PONTOS";

    echo sprintf(
        "[%s] #%d - %s (R$ %.2f) %s\n",
        $lanc->created_at->format('Y-m-d H:i:s'),
        $lanc->id,
        $lanc->descricao,
        $lanc->valor,
        $pontosGanhos
    );
}

echo "\n";

// 7. Análise de possíveis problemas
echo "🐛 ANÁLISE DE POSSÍVEIS PROBLEMAS:\n";
echo "─────────────────────────────────────────────────────────────\n";

// Verificar se total de pontos nos logs bate com o progresso
if ($progress && $totalPontos != $progress->total_points) {
    echo "⚠️  DIVERGÊNCIA: Total nos logs ({$totalPontos}) != Progresso ({$progress->total_points})\n";
    echo "   Diferença: " . ($progress->total_points - $totalPontos) . " pontos\n\n";
}

// Verificar se número de lançamentos bate com logs CREATE_LANCAMENTO
$createLancamentoLogs = $logs->where('action', 'create_lancamento')->count();
if ($createLancamentoLogs != $lancamentos->count()) {
    echo "⚠️  POSSÍVEL PROBLEMA: Lançamentos ({$lancamentos->count()}) != Logs CREATE_LANCAMENTO ({$createLancamentoLogs})\n";
    echo "   Diferença: " . abs($lancamentos->count() - $createLancamentoLogs) . "\n\n";
}

// Verificar se houve múltiplos daily_login no mesmo dia
$dailyLogins = $logs->where('action', 'daily_login');
$loginsByDate = $dailyLogins->groupBy(function ($log) {
    return $log->created_at->format('Y-m-d');
});

foreach ($loginsByDate as $date => $logsInDay) {
    if ($logsInDay->count() > 1) {
        echo "⚠️  MÚLTIPLOS DAILY_LOGIN no mesmo dia ({$date}): {$logsInDay->count()} vezes\n";
        $totalPointsInDay = $logsInDay->sum('points');
        echo "   Total de pontos ganhos: {$totalPointsInDay}\n\n";
    }
}

echo "\n✅ Análise concluída!\n\n";
