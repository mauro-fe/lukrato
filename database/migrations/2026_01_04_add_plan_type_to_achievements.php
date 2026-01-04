<?php

/**
 * Migration: Adicionar campo plan_type às conquistas
 * Data: 2026-01-04
 */

require_once dirname(__DIR__, 2) . '/bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

// Verificar se precisa adicionar campos na tabela achievements
$schema = DB::schema();

if (!$schema->hasColumn('achievements', 'plan_type')) {
    DB::statement("ALTER TABLE achievements ADD COLUMN plan_type VARCHAR(20) DEFAULT 'free' COMMENT 'free, pro, all' AFTER category");
    echo "✓ Campo plan_type adicionado à tabela achievements\n";
}

if (!$schema->hasColumn('achievements', 'sort_order')) {
    DB::statement("ALTER TABLE achievements ADD COLUMN sort_order INT DEFAULT 0 AFTER plan_type");
    echo "✓ Campo sort_order adicionado à tabela achievements\n";
}

echo "\n✅ Migration de conquistas concluída!\n";
