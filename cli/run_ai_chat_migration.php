<?php

/**
 * Migration para criar tabelas do chat de IA (conversations + messages).
 *
 * Uso: php cli/run_ai_chat_migration.php
 */

require dirname(__DIR__) . '/bootstrap.php';

use Illuminate\Database\Capsule\Manager as Capsule;

echo "=================================================\n";
echo "  MIGRATION: Chat de IA (Conversations)\n";
echo "=================================================\n\n";

// Verificar conexão
try {
    Capsule::connection()->getPdo();
    echo "✅ Conexão com banco de dados OK\n\n";
} catch (\Exception $e) {
    echo "❌ Erro ao conectar com banco de dados: " . $e->getMessage() . "\n";
    exit(1);
}

// Buscar migrations já executadas
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
    '2026_03_09_create_ai_chat_tables.php',
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
        echo "⏭️  Pulando (já executada): $basename\n";
        continue;
    }

    echo "▶️  Executando: $basename\n";

    try {
        $migration = require $file;

        if (is_object($migration) && method_exists($migration, 'up')) {
            $migration->up();
        } else {
            echo "  ⚠️ Migration não possui método up()\n";
            continue;
        }

        Capsule::table('migrations')->insert([
            'migration' => $migrationName,
            'batch'     => $nextBatch,
        ]);

        $executadasAgora++;
        echo "  ✅ Concluída com sucesso!\n";
    } catch (\Exception $e) {
        echo "  ❌ Erro: " . $e->getMessage() . "\n";
        $erros++;
    }
}

echo "\n=================================================\n";
echo "  RESUMO\n";
echo "=================================================\n";
echo "  Migrations executadas: $executadasAgora\n";
echo "  Erros: $erros\n";
echo "=================================================\n\n";

if ($erros === 0) {
    echo "✅ Chat de IA pronto para uso!\n\n";
} else {
    echo "⚠️ Algumas migrations falharam. Verifique os erros acima.\n\n";
}
