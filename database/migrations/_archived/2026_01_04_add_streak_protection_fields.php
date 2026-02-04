<?php

/**
 * Migration: Adicionar campos de proteção de streak para usuários Pro
 * Data: 2026-01-04
 */

use Illuminate\Database\Capsule\Manager as DB;

// Verificar se precisa adicionar campos na tabela user_progress
$schema = DB::schema();

if (!$schema->hasColumn('user_progress', 'streak_freeze_used_this_month')) {
    DB::statement("ALTER TABLE user_progress ADD COLUMN streak_freeze_used_this_month BOOLEAN DEFAULT FALSE AFTER best_streak");
    echo "✓ Campo streak_freeze_used_this_month adicionado\n";
}

if (!$schema->hasColumn('user_progress', 'streak_freeze_date')) {
    DB::statement("ALTER TABLE user_progress ADD COLUMN streak_freeze_date DATE NULL AFTER streak_freeze_used_this_month");
    echo "✓ Campo streak_freeze_date adicionado\n";
}

if (!$schema->hasColumn('user_progress', 'streak_freeze_month')) {
    DB::statement("ALTER TABLE user_progress ADD COLUMN streak_freeze_month VARCHAR(7) NULL COMMENT 'YYYY-MM do mês do freeze' AFTER streak_freeze_date");
    echo "✓ Campo streak_freeze_month adicionado\n";
}

echo "\n✅ Migration de proteção de streak concluída!\n";
