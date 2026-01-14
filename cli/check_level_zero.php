#!/usr/bin/env php
<?php
/**
 * Script para verificar usuários com nível 0
 */

require_once dirname(__DIR__) . '/bootstrap.php';

use Application\Models\UserProgress;

echo "🔍 Verificando usuários com nível 0...\n";
echo str_repeat("=", 60) . "\n\n";

try {
    $usersWithZeroLevel = UserProgress::where('current_level', 0)->get();

    echo "Total de usuários com nível 0: " . $usersWithZeroLevel->count() . "\n\n";

    foreach ($usersWithZeroLevel as $progress) {
        echo "👤 User ID: {$progress->user_id}\n";
        echo "   Total de pontos: {$progress->total_points}\n";
        echo "   Nível: {$progress->current_level} ❌\n";
        echo "   Pontos para próximo: {$progress->points_to_next_level}\n\n";

        // Corrigir para nível 1
        $progress->current_level = 1;
        $progress->points_to_next_level = 300;
        $progress->save();

        echo "   ✅ CORRIGIDO para nível 1\n\n";
    }
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
