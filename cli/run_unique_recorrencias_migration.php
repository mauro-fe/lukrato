<?php

/**
 * CLI: Executar migration de blindagem anti-duplicidade de recorrencias.
 *
 * Uso: php cli/run_unique_recorrencias_migration.php
 */

require dirname(__DIR__) . '/bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

echo "=== Executando migration de blindagem de recorrencias ===\n\n";

$migrationFile = __DIR__ . '/../database/migrations/2026_03_05_add_unique_indexes_recorrencias.php';
$migrationName = '2026_03_05_add_unique_indexes_recorrencias';

if (!file_exists($migrationFile)) {
    echo "❌ Migration nao encontrada: {$migrationFile}\n";
    exit(1);
}

$migration = require $migrationFile;

if (!is_object($migration) || !method_exists($migration, 'up')) {
    echo "❌ Arquivo de migration invalido.\n";
    exit(1);
}

try {
    $migration->up();

    $jaRegistrada = DB::table('migrations')->where('migration', $migrationName)->exists();
    if (!$jaRegistrada) {
        $batch = (int) DB::table('migrations')->max('batch') + 1;
        DB::table('migrations')->insert([
            'migration' => $migrationName,
            'batch' => $batch,
        ]);
        echo "\n✅ Migration registrada no batch {$batch}.\n";
    } else {
        echo "\nℹ️ Migration ja estava registrada na tabela migrations.\n";
    }

    echo "\n✅ Processo concluido com sucesso.\n";
} catch (\Throwable $e) {
    echo "\n❌ Erro ao aplicar migration: " . $e->getMessage() . "\n";
    exit(1);
}
