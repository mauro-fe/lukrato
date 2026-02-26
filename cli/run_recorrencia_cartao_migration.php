<?php

/**
 * CLI: Executar migration de recorrência em faturas_cartao_itens
 * 
 * Uso: php cli/run_recorrencia_cartao_migration.php
 */

require dirname(__DIR__) . '/bootstrap.php';

echo "=== Executando migration de recorrência no cartão de crédito ===\n\n";

$migration = require __DIR__ . '/../database/migrations/2026_02_28_add_recorrencia_to_faturas_cartao_itens.php';

try {
    $migration->up();

    // Registrar na tabela de migrations
    $migrationName = '2026_02_28_add_recorrencia_to_faturas_cartao_itens';
    $exists = \Illuminate\Database\Capsule\Manager::table('migrations')
        ->where('migration', $migrationName)
        ->exists();

    if (!$exists) {
        $batch = (int)\Illuminate\Database\Capsule\Manager::table('migrations')->max('batch') + 1;
        \Illuminate\Database\Capsule\Manager::table('migrations')->insert([
            'migration' => $migrationName,
            'batch' => $batch,
        ]);
        echo "\n✅ Migration registrada (batch {$batch}).\n";
    } else {
        echo "\nℹ️ Migration já estava registrada.\n";
    }
} catch (\Throwable $e) {
    echo "\n❌ Erro: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n✅ Tudo pronto!\n";
