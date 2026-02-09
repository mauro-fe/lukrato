<?php

/**
 * Script para executar as migrations do sistema de Comunicações/Notificações
 * 
 * Cria as tabelas:
 * - notifications: notificações individuais por usuário
 * - message_campaigns: histórico de campanhas enviadas
 * 
 * Uso: php cli/run_notifications_migration.php
 */

require dirname(__DIR__) . '/bootstrap.php';

use Illuminate\Database\Capsule\Manager as Capsule;

echo "=================================================\n";
echo "  MIGRATION: Sistema de Comunicações/Notificações\n";
echo "=================================================\n\n";

// Verificar conexão com banco
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
        echo "⚠️ Tabela 'migrations' não existe. Criando...\n";
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

// Lista de migrations para executar
$migrationsToRun = [
    '2026_02_08_create_notifications_table.php',
    '2026_02_08_create_message_campaigns_table.php',
];

$migrationsPath = dirname(__DIR__) . '/database/migrations';
$executadasAgora = 0;
$erros = 0;

foreach ($migrationsToRun as $basename) {
    $migrationName = str_replace('.php', '', $basename);
    $file = $migrationsPath . '/' . $basename;

    // Verificar se arquivo existe
    if (!file_exists($file)) {
        echo "⚠️ Arquivo não encontrado: $basename\n";
        $erros++;
        continue;
    }

    // Pular se já foi executada
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

        // Registrar na tabela de migrations
        Capsule::table('migrations')->insert([
            'migration' => $migrationName,
            'batch' => $nextBatch
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
    echo "✅ Sistema de Comunicações pronto para uso!\n";
    echo "\n  Acesse: /sysadmin/comunicacoes\n\n";
} else {
    echo "⚠️ Algumas migrations falharam. Verifique os erros acima.\n\n";
}
