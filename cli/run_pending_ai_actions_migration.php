<?php

/**
 * Migration para criar tabela pending_ai_actions.
 *
 * Uso: php cli/run_pending_ai_actions_migration.php
 */

require dirname(__DIR__) . '/bootstrap.php';

use Illuminate\Database\Capsule\Manager as Capsule;

echo "=================================================\n";
echo "  MIGRATION: Pending AI Actions\n";
echo "=================================================\n\n";

try {
    Capsule::connection()->getPdo();
    echo "✅ Conexão com banco de dados OK\n\n";
} catch (\Exception $e) {
    echo "❌ Erro ao conectar com banco de dados: " . $e->getMessage() . "\n";
    exit(1);
}

try {
    if (!Capsule::schema()->hasTable('migrations')) {
        Capsule::schema()->create('migrations', function ($table) {
            $table->id();
            $table->string('migration');
            $table->integer('batch');
        });
    }

    $executadas = Capsule::table('migrations')->pluck('migration')->toArray();
    $nextBatch = (int) Capsule::table('migrations')->max('batch') + 1;
} catch (\Exception $e) {
    echo "❌ Erro ao verificar migrations: " . $e->getMessage() . "\n";
    exit(1);
}

$migrationsToRun = [
    '2026_03_10_create_pending_ai_actions.php',
];

$migrationsPath = dirname(__DIR__) . '/database/migrations';
$executadasAgora = 0;
$erros = 0;

foreach ($migrationsToRun as $basename) {
    $migrationName = str_replace('.php', '', $basename);
    $file = $migrationsPath . '/' . $basename;

    if (!file_exists($file)) {
        echo "⚠️ Arquivo não encontrado: $basename\n";
        $erros++;
        continue;
    }

    if (in_array($migrationName, $executadas)) {
        echo "⏭️  Já executada: $migrationName\n";
        continue;
    }

    echo "▶ Executando: $migrationName\n";

    try {
        $migration = require $file;
        $migration->up();

        Capsule::table('migrations')->insert([
            'migration' => $migrationName,
            'batch'     => $nextBatch,
        ]);

        $executadasAgora++;
        echo "  ✅ OK\n\n";
    } catch (\Exception $e) {
        echo "  ❌ Erro: " . $e->getMessage() . "\n\n";
        $erros++;
    }
}

echo "=================================================\n";
echo "  Executadas: $executadasAgora | Erros: $erros\n";
echo "=================================================\n";

exit($erros > 0 ? 1 : 0);
