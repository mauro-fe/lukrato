<?php

/**
 * ==============================================
 * TESTE COMPLETO DO SISTEMA DE GAMIFICAÇÃO
 * ==============================================
 * 
 * Este script testa todos os componentes do sistema de gamificação:
 * - Criação de lançamentos e acúmulo de pontos
 * - Criação de categorias e conquistas
 * - Sistema de streaks diário
 * - Progressão de níveis
 * - Desbloqueio de conquistas
 * - Verificação de integridade dos dados
 */

require_once __DIR__ . '/../bootstrap.php';

use Application\Services\GamificationService;
use Application\Models\UserProgress;
use Application\Models\Achievement;
use Application\Models\UserAchievement;
use Application\Models\PointsLog;
use Application\Models\Lancamento;
use Application\Models\Categoria;
use Application\Enums\GamificationAction;
use Carbon\Carbon;

// ==========================================
// CONFIGURAÇÃO
// ==========================================
$TEST_USER_ID = 1; // ID do usuário de teste (ajuste conforme necessário)

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║         TESTE COMPLETO - SISTEMA DE GAMIFICAÇÃO            ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "\n";

$gamification = new GamificationService();

// ==========================================
// TESTE 1: Verificar estrutura do banco
// ==========================================
echo "📊 TESTE 1: Verificando estrutura do banco de dados...\n";
echo str_repeat("-", 60) . "\n";

try {
    $tables = ['user_progress', 'achievements', 'user_achievements', 'points_log'];
    foreach ($tables as $table) {
        $query = "SHOW TABLES LIKE '{$table}'";
        $result = \Illuminate\Support\Facades\DB::select($query);
        $exists = !empty($result);
        echo ($exists ? "✅" : "❌") . " Tabela '{$table}': " . ($exists ? "OK" : "NÃO ENCONTRADA") . "\n";
    }

    $achievementCount = Achievement::count();
    echo "\n📋 Total de conquistas cadastradas: {$achievementCount}\n";

    if ($achievementCount > 0) {
        echo "\nConquistas disponíveis:\n";
        Achievement::all()->each(function ($achievement) {
            echo "  • {$achievement->name} ({$achievement->code}) - {$achievement->points_reward} pts\n";
        });
    }
} catch (Exception $e) {
    echo "❌ Erro ao verificar estrutura: " . $e->getMessage() . "\n";
}

echo "\n";

// ==========================================
// TESTE 2: Progresso inicial do usuário
// ==========================================
echo "👤 TESTE 2: Verificando progresso inicial do usuário #{$TEST_USER_ID}...\n";
echo str_repeat("-", 60) . "\n";

try {
    $progress = UserProgress::where('user_id', $TEST_USER_ID)->first();

    if ($progress) {
        echo "✅ Usuário tem progresso registrado:\n";
        echo "  • Pontos Totais: {$progress->total_points}\n";
        echo "  • Nível Atual: {$progress->current_level}\n";
        echo "  • Pontos para Próximo Nível: {$progress->points_to_next_level}\n";
        echo "  • Progresso: {$progress->progress_percentage}%\n";
        echo "  • Streak Atual: {$progress->current_streak} dias\n";
        echo "  • Melhor Streak: {$progress->best_streak} dias\n";
        echo "  • Última Atividade: " . ($progress->last_activity_date ? $progress->last_activity_date->format('d/m/Y') : 'Nunca') . "\n";
    } else {
        echo "ℹ️  Usuário ainda não tem progresso registrado (será criado automaticamente)\n";
    }
} catch (Exception $e) {
    echo "❌ Erro ao verificar progresso: " . $e->getMessage() . "\n";
}

echo "\n";

// ==========================================
// TESTE 3: Adicionar pontos por lançamento
// ==========================================
echo "💰 TESTE 3: Testando adição de pontos (CREATE_LANCAMENTO)...\n";
echo str_repeat("-", 60) . "\n";

try {
    $result = $gamification->addPoints(
        $TEST_USER_ID,
        GamificationAction::CREATE_LANCAMENTO,
        999,
        'lancamento',
        ['description' => 'Teste de lançamento']
    );

    echo "✅ Pontos adicionados com sucesso:\n";
    echo "  • Pontos Ganhos: {$result['points_gained']}\n";
    echo "  • Total de Pontos: {$result['total_points']}\n";
    echo "  • Nível Atual: {$result['level']}\n";
    echo "  • Subiu de Nível: " . ($result['level_up'] ? 'SIM 🎉' : 'NÃO') . "\n";
    echo "  • Progresso: {$result['progress_percentage']}%\n";

    if (!empty($result['new_achievements'])) {
        echo "  • 🏆 NOVAS CONQUISTAS: " . count($result['new_achievements']) . "\n";
        foreach ($result['new_achievements'] as $achievement) {
            echo "    → {$achievement['name']} (+{$achievement['points_reward']} pts)\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Erro ao adicionar pontos: " . $e->getMessage() . "\n";
}

echo "\n";

// ==========================================
// TESTE 4: Adicionar pontos por categoria
// ==========================================
echo "📂 TESTE 4: Testando adição de pontos (CREATE_CATEGORIA)...\n";
echo str_repeat("-", 60) . "\n";

try {
    $result = $gamification->addPoints(
        $TEST_USER_ID,
        GamificationAction::CREATE_CATEGORIA,
        888,
        'categoria',
        ['description' => 'Teste de categoria']
    );

    echo "✅ Pontos adicionados com sucesso:\n";
    echo "  • Pontos Ganhos: {$result['points_gained']}\n";
    echo "  • Total de Pontos: {$result['total_points']}\n";
    echo "  • Nível Atual: {$result['level']}\n";

    if (!empty($result['new_achievements'])) {
        echo "  • 🏆 NOVAS CONQUISTAS: " . count($result['new_achievements']) . "\n";
        foreach ($result['new_achievements'] as $achievement) {
            echo "    → {$achievement['name']} (+{$achievement['points_reward']} pts)\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Erro ao adicionar pontos: " . $e->getMessage() . "\n";
}

echo "\n";

// ==========================================
// TESTE 5: Sistema de streaks
// ==========================================
echo "🔥 TESTE 5: Testando sistema de streaks diário...\n";
echo str_repeat("-", 60) . "\n";

try {
    $result = $gamification->updateStreak($TEST_USER_ID);

    echo "✅ Streak atualizado:\n";
    echo "  • Streak Atual: {$result['streak']} dias\n";
    echo "  • Melhor Streak: {$result['best_streak']} dias\n";
    echo "  • Pontos de Atividade Diária: {$result['daily_points_gained']}\n";
} catch (Exception $e) {
    echo "❌ Erro ao atualizar streak: " . $e->getMessage() . "\n";
}

echo "\n";

// ==========================================
// TESTE 6: Histórico de pontos
// ==========================================
echo "📜 TESTE 6: Verificando histórico de pontos...\n";
echo str_repeat("-", 60) . "\n";

try {
    $history = PointsLog::where('user_id', $TEST_USER_ID)
        ->orderBy('created_at', 'desc')
        ->limit(10)
        ->get();

    echo "✅ Últimas {$history->count()} entradas no histórico:\n";

    foreach ($history as $entry) {
        $date = $entry->created_at->format('d/m/Y H:i');
        $points = $entry->points > 0 ? "+{$entry->points}" : $entry->points;
        echo "  • [{$date}] {$points} pts - {$entry->description}\n";
    }

    // Estatísticas
    $totalGains = PointsLog::where('user_id', $TEST_USER_ID)->gains()->sum('points');
    $totalLosses = abs(PointsLog::where('user_id', $TEST_USER_ID)->losses()->sum('points'));

    echo "\n📊 Estatísticas:\n";
    echo "  • Total de Ganhos: +{$totalGains} pts\n";
    echo "  • Total de Perdas: -{$totalLosses} pts\n";
    echo "  • Saldo: " . ($totalGains - $totalLosses) . " pts\n";
} catch (Exception $e) {
    echo "❌ Erro ao buscar histórico: " . $e->getMessage() . "\n";
}

echo "\n";

// ==========================================
// TESTE 7: Conquistas do usuário
// ==========================================
echo "🏆 TESTE 7: Verificando conquistas do usuário...\n";
echo str_repeat("-", 60) . "\n";

try {
    $unlockedAchievements = UserAchievement::where('user_id', $TEST_USER_ID)
        ->with('achievement')
        ->orderBy('unlocked_at', 'desc')
        ->get();

    $totalAchievements = Achievement::count();
    $unlockedCount = $unlockedAchievements->count();
    $percentage = $totalAchievements > 0 ? round(($unlockedCount / $totalAchievements) * 100, 1) : 0;

    echo "✅ Conquistas desbloqueadas: {$unlockedCount}/{$totalAchievements} ({$percentage}%)\n\n";

    if ($unlockedAchievements->isEmpty()) {
        echo "ℹ️  Nenhuma conquista desbloqueada ainda.\n";
    } else {
        foreach ($unlockedAchievements as $ua) {
            $date = $ua->unlocked_at->format('d/m/Y H:i');
            $icon = $ua->achievement->icon;
            $name = $ua->achievement->name;
            $points = $ua->achievement->points_reward;
            $seen = $ua->notification_seen ? "✓" : "NEW";

            echo "  {$icon} [{$date}] {$name} (+{$points} pts) [{$seen}]\n";
            echo "     {$ua->achievement->description}\n\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Erro ao buscar conquistas: " . $e->getMessage() . "\n";
}

echo "\n";

// ==========================================
// TESTE 8: Anti-duplicação
// ==========================================
echo "🛡️  TESTE 8: Testando sistema anti-duplicação...\n";
echo str_repeat("-", 60) . "\n";

try {
    echo "Tentando adicionar pontos pelo mesmo lançamento novamente...\n";

    $result = $gamification->addPoints(
        $TEST_USER_ID,
        GamificationAction::CREATE_LANCAMENTO,
        999, // Mesmo ID usado no Teste 3
        'lancamento'
    );

    echo "⚠️  Pontos adicionados (NÃO DEVERIA ACONTECER): {$result['points_gained']}\n";
} catch (Exception $e) {
    echo "✅ Sistema anti-duplicação funcionando: Ação já registrada\n";
}

echo "\n";

// ==========================================
// TESTE 9: Progresso final
// ==========================================
echo "🎯 TESTE 9: Verificando progresso final...\n";
echo str_repeat("-", 60) . "\n";

try {
    $finalProgress = UserProgress::where('user_id', $TEST_USER_ID)->first();

    if ($finalProgress) {
        echo "✅ RESUMO FINAL DO PROGRESSO:\n";
        echo "\n";
        echo "  📊 PONTOS E NÍVEL\n";
        echo "     • Total de Pontos: {$finalProgress->total_points}\n";
        echo "     • Nível Atual: {$finalProgress->current_level}/5\n";
        echo "     • Pontos para Próximo Nível: {$finalProgress->points_to_next_level}\n";
        echo "     • Progresso: {$finalProgress->progress_percentage}%\n";
        echo "\n";
        echo "  🔥 STREAKS\n";
        echo "     • Streak Atual: {$finalProgress->current_streak} dias\n";
        echo "     • Melhor Streak: {$finalProgress->best_streak} dias\n";
        echo "     • Última Atividade: " . ($finalProgress->last_activity_date ? $finalProgress->last_activity_date->format('d/m/Y') : 'Nunca') . "\n";
        echo "\n";
        echo "  🏆 CONQUISTAS\n";
        $achievementCount = UserAchievement::where('user_id', $TEST_USER_ID)->count();
        $totalAchievements = Achievement::count();
        $percentage = $totalAchievements > 0 ? round(($achievementCount / $totalAchievements) * 100, 1) : 0;
        echo "     • Desbloqueadas: {$achievementCount}/{$totalAchievements} ({$percentage}%)\n";

        // Barra de progresso visual
        $barLength = 30;
        $filledLength = (int) round(($finalProgress->progress_percentage / 100) * $barLength);
        $bar = str_repeat('█', $filledLength) . str_repeat('░', $barLength - $filledLength);
        echo "\n";
        echo "  📈 PROGRESSO PARA NÍVEL " . ($finalProgress->current_level + 1) . "\n";
        echo "     [{$bar}] {$finalProgress->progress_percentage}%\n";
    }
} catch (Exception $e) {
    echo "❌ Erro ao verificar progresso final: " . $e->getMessage() . "\n";
}

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║                   TESTES FINALIZADOS ✅                    ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "\n";

echo "💡 PRÓXIMOS PASSOS:\n";
echo "   1. Teste os endpoints da API:\n";
echo "      • GET  /api/gamification/progress\n";
echo "      • GET  /api/gamification/achievements\n";
echo "      • POST /api/gamification/achievements/mark-seen\n";
echo "      • GET  /api/gamification/leaderboard\n";
echo "\n";
echo "   2. Integre no frontend para exibir:\n";
echo "      • Badge de nível e pontos\n";
echo "      • Indicador de streak\n";
echo "      • Notificações de conquistas\n";
echo "      • Ranking de usuários\n";
echo "\n";
echo "   3. Configure notificações visuais quando:\n";
echo "      • Usuário ganha pontos\n";
echo "      • Usuário sobe de nível\n";
echo "      • Nova conquista desbloqueada\n";
echo "      • Streak atingir marcos (7, 30 dias)\n";
echo "\n";
