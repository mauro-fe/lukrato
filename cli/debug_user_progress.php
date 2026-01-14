#!/usr/bin/env php
<?php
/**
 * Script para debugar o progresso de gamificação do usuário
 */

require_once dirname(__DIR__) . '/bootstrap.php';

use Application\Models\UserProgress;
use Application\Models\User;

echo "🔍 Verificando progresso de todos os usuários...\n";
echo str_repeat("=", 60) . "\n\n";

try {
    $progressRecords = UserProgress::with('user')->get();

    foreach ($progressRecords as $progress) {
        $user = $progress->user;
        $userName = $user ? $user->nome : 'Usuário não encontrado';

        echo "👤 {$userName} (ID: {$progress->user_id})\n";
        echo "   📊 Total de pontos: {$progress->total_points}\n";
        echo "   🎯 Nível atual: {$progress->current_level}\n";
        echo "   ⬆️  Pontos para próximo nível: {$progress->points_to_next_level}\n";
        echo "   📅 Última atividade: " . ($progress->last_activity_date ?? 'Nunca') . "\n";
        echo "\n";
    }
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
