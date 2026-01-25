#!/usr/bin/env php
<?php
/**
 * Script para verificação mensal de conquistas
 * 
 * Deve ser executado no primeiro dia de cada mês (via cron/scheduler)
 * para verificar conquistas que dependem de fechamento de mês:
 * - Mês Vitorioso (saldo positivo)
 * - Poupador/Investidor/Milionário (economia %)
 * - Perfeccionista (todas despesas categorizadas)
 * - Meses consecutivos positivos
 * 
 * Uso: php cli/check_monthly_achievements.php
 * Cron sugerido: 0 1 1 * * php /path/to/cli/check_monthly_achievements.php
 */

require_once dirname(__DIR__) . '/bootstrap.php';

use Application\Models\Usuario;
use Application\Models\UserProgress;
use Application\Services\AchievementService;
use Carbon\Carbon;

echo "🏆 ======================================\n";
echo "   LUKRATO - VERIFICAÇÃO MENSAL DE CONQUISTAS\n";
echo "======================================\n\n";

$startTime = microtime(true);
$achievementService = new AchievementService();

// Mês anterior (que acabou de fechar)
$lastMonth = Carbon::now()->subMonth();
echo "📅 Verificando conquistas do mês: " . $lastMonth->format('F/Y') . "\n\n";

try {
    // Buscar todos os usuários ativos
    $users = Usuario::where('ativo', 1)->get();

    echo "👥 Total de usuários ativos: " . $users->count() . "\n";
    echo str_repeat("-", 50) . "\n\n";

    $totalUnlocked = 0;
    $usersWithNewAchievements = 0;

    foreach ($users as $user) {
        // Verificar conquistas para o usuário
        $newAchievements = $achievementService->checkAndUnlockAchievements($user->id, 'monthly_check');

        if (!empty($newAchievements)) {
            $usersWithNewAchievements++;
            $totalUnlocked += count($newAchievements);

            echo "✅ {$user->nome} (ID: {$user->id}):\n";
            foreach ($newAchievements as $ach) {
                echo "   🎖️ {$ach['name']} (+{$ach['points_reward']} pts)\n";
            }
            echo "\n";
        }
    }

    $elapsed = round(microtime(true) - $startTime, 2);

    echo str_repeat("=", 50) . "\n";
    echo "📊 RESUMO:\n";
    echo "   • Usuários verificados: " . $users->count() . "\n";
    echo "   • Usuários com novas conquistas: {$usersWithNewAchievements}\n";
    echo "   • Total de conquistas desbloqueadas: {$totalUnlocked}\n";
    echo "   • Tempo de execução: {$elapsed}s\n";
    echo str_repeat("=", 50) . "\n";
    echo "✅ Verificação mensal concluída com sucesso!\n";

    // Log para arquivo
    $logMessage = sprintf(
        "[%s] Monthly achievements check: %d users, %d new achievements unlocked in %.2fs\n",
        date('Y-m-d H:i:s'),
        $users->count(),
        $totalUnlocked,
        $elapsed
    );

    $logFile = dirname(__DIR__) . '/storage/logs/achievements.log';
    file_put_contents($logFile, $logMessage, FILE_APPEND);
} catch (\Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";

    // Log de erro
    $logFile = dirname(__DIR__) . '/storage/logs/achievements.log';
    $errorLog = sprintf(
        "[%s] ERROR in monthly achievements check: %s\n",
        date('Y-m-d H:i:s'),
        $e->getMessage()
    );
    file_put_contents($logFile, $errorLog, FILE_APPEND);

    exit(1);
}
