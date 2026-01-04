#!/usr/bin/env php
<?php
/**
 * Script de teste para API de gamificação
 * Verifica se as tabelas e dados existem
 */

require_once dirname(__DIR__) . '/bootstrap.php';

use Application\Models\UserProgress;
use Application\Models\Achievement;
use Application\Models\Usuario;
use Illuminate\Database\Capsule\Manager as DB;

echo "🎮 Testando Sistema de Gamificação\n";
echo str_repeat("=", 60) . "\n\n";

// 1. Verificar se tabelas existem
echo "1️⃣ Verificando tabelas...\n";

try {
    $tables = [
        'user_progress',
        'achievements',
        'user_achievements',
        'points_logs'
    ];

    foreach ($tables as $table) {
        $exists = DB::schema()->hasTable($table);
        echo ($exists ? "✅" : "❌") . " Tabela '{$table}': " . ($exists ? "existe" : "NÃO EXISTE") . "\n";
    }
    echo "\n";
} catch (\Exception $e) {
    echo "❌ Erro ao verificar tabelas: " . $e->getMessage() . "\n\n";
}

// 2. Verificar usuários
echo "2️⃣ Verificando usuários...\n";
try {
    $users = Usuario::limit(5)->get(['id', 'nome', 'plano']);
    echo "Total de usuários: " . Usuario::count() . "\n";

    foreach ($users as $user) {
        echo "  - ID: {$user->id}, Nome: {$user->nome}, Plano: " . ($user->plano ?? 'free') . "\n";
    }
    echo "\n";
} catch (\Exception $e) {
    echo "❌ Erro ao buscar usuários: " . $e->getMessage() . "\n\n";
}

// 3. Verificar conquistas
echo "3️⃣ Verificando conquistas...\n";
try {
    $achievements = Achievement::all();
    echo "Total de conquistas cadastradas: " . $achievements->count() . "\n";

    if ($achievements->count() === 0) {
        echo "⚠️  Nenhuma conquista cadastrada! Execute o seed:\n";
        echo "   php database/migrations/seed_achievements.php\n";
    } else {
        foreach ($achievements as $ach) {
            echo "  - {$ach->code}: {$ach->name} ({$ach->plan_type})\n";
        }
    }
    echo "\n";
} catch (\Exception $e) {
    echo "❌ Erro ao buscar conquistas: " . $e->getMessage() . "\n\n";
}

// 4. Verificar progresso de usuários
echo "4️⃣ Verificando progresso de usuários...\n";
try {
    $progress = UserProgress::with('user')->limit(5)->get();
    echo "Total de registros de progresso: " . UserProgress::count() . "\n";

    if ($progress->count() === 0) {
        echo "⚠️  Nenhum progresso registrado ainda\n";
    } else {
        foreach ($progress as $p) {
            echo "  - User {$p->user_id} ({$p->user->nome}): Nível {$p->current_level}, {$p->total_points} pontos\n";
        }
    }
    echo "\n";
} catch (\Exception $e) {
    echo "❌ Erro ao buscar progresso: " . $e->getMessage() . "\n\n";
}

// 5. Testar API internamente
echo "5️⃣ Testando lógica da API...\n";
try {
    $firstUser = Usuario::first();

    if (!$firstUser) {
        echo "❌ Nenhum usuário encontrado para teste\n\n";
    } else {
        echo "Testando com usuário: {$firstUser->nome} (ID: {$firstUser->id})\n";

        // Simular controller
        $progress = UserProgress::where('user_id', $firstUser->id)->first();

        if (!$progress) {
            echo "⚠️  Usuário não tem progresso ainda (será criado no primeiro uso)\n";
            $progressData = [
                'total_points' => 0,
                'current_level' => 1,
                'points_to_next_level' => 300,
                'progress_percentage' => 0,
                'current_streak' => 0,
                'best_streak' => 0,
                'is_pro' => $firstUser->isPro(),
            ];
        } else {
            $progressData = [
                'total_points' => $progress->total_points,
                'current_level' => $progress->current_level,
                'points_to_next_level' => $progress->points_to_next_level,
                'current_streak' => $progress->current_streak,
                'best_streak' => $progress->best_streak,
                'is_pro' => $firstUser->isPro(),
            ];
        }

        echo "✅ Dados de progresso:\n";
        echo json_encode($progressData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
    }
} catch (\Exception $e) {
    echo "❌ Erro ao testar API: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n\n";
}

echo str_repeat("=", 60) . "\n";
echo "✅ Teste concluído!\n";
