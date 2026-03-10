<?php

/**
 * Migration para corrigir o enum ai_logs.type e adicionar os novos intent types.
 *
 * Uso: php cli/run_fix_ai_logs_enum.php
 */

require dirname(__DIR__) . '/bootstrap.php';

use Illuminate\Database\Capsule\Manager as Capsule;

echo "=================================================\n";
echo "  MIGRATION: Fix ai_logs.type ENUM\n";
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

$basename = '2026_03_10_fix_ai_logs_type_enum.php';
$migrationName = str_replace('.php', '', $basename);
$file = dirname(__DIR__) . '/database/migrations/' . $basename;

if (!file_exists($file)) {
    echo "❌ Arquivo não encontrado: $basename\n";
    exit(1);
}

if (in_array($migrationName, $executadas)) {
    echo "⏭️  Já executada: $basename\n";
    exit(0);
}

echo "▶️  Executando: $basename\n";

try {
    $migration = require $file;

    if (is_object($migration) && method_exists($migration, 'up')) {
        $migration->up();
    } else {
        echo "  ⚠️ Migration não possui método up()\n";
        exit(1);
    }

    Capsule::table('migrations')->insert([
        'migration' => $migrationName,
        'batch'     => $nextBatch,
    ]);

    echo "\n✅ Migration concluída com sucesso!\n";
} catch (\Exception $e) {
    echo "  ❌ Erro: " . $e->getMessage() . "\n";
    exit(1);
}
