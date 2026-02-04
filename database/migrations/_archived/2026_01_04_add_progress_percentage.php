<?php

/**
 * Migration: Adicionar coluna progress_percentage
 */

require_once dirname(__DIR__, 2) . '/bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

echo "🔄 Adicionando coluna progress_percentage...\n";

try {
    $schema = DB::schema();

    if (!$schema->hasColumn('user_progress', 'progress_percentage')) {
        DB::statement("ALTER TABLE user_progress ADD COLUMN progress_percentage DECIMAL(5,2) DEFAULT 0 AFTER points_to_next_level");
        echo "✅ Coluna progress_percentage adicionada!\n";
    } else {
        echo "⚠️  Coluna progress_percentage já existe!\n";
    }
} catch (\Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    exit(1);
}
