<?php

/**
 * CLI: Adiciona create_entity e confirm_action ao ENUM ai_logs.type
 *
 * Uso: php cli/run_entity_types_ai_logs_migration.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

echo "\n╔════════════════════════════════════════════╗\n";
echo "║  MIGRATION: Add entity types to ai_logs   ║\n";
echo "╚════════════════════════════════════════════╝\n\n";

use Illuminate\Database\Capsule\Manager as Capsule;

try {
    Capsule::connection()->getPdo();
    echo "✅ Conexão com banco OK\n\n";
} catch (\Exception $e) {
    echo "❌ Falha na conexão: {$e->getMessage()}\n";
    exit(1);
}

$basename = '2026_03_10_add_entity_types_ai_logs_enum.php';
$file = __DIR__ . '/../database/migrations/' . $basename;

if (!file_exists($file)) {
    echo "❌ Arquivo de migração não encontrado: {$basename}\n";
    exit(1);
}

$alreadyRan = Capsule::table('migrations')->where('migration', $basename)->exists();
if ($alreadyRan) {
    echo "⚠️ Migração já executada: {$basename}\n";
    exit(0);
}

echo "▶ Executando: {$basename}\n";
$migration = require $file;
$migration->up();

Capsule::table('migrations')->insert([
    'migration'  => $basename,
    'batch'      => Capsule::table('migrations')->max('batch') + 1,
]);

echo "\n✅ Migração concluída com sucesso!\n";
