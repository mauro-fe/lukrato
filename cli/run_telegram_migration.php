<?php

/**
 * CLI: Executar migrações do Telegram.
 *
 * Uso:
 *   php cli/run_telegram_migration.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

echo "=== Migrações Telegram ===\n\n";

$migrations = [
    '2026_03_12_add_telegram_to_usuarios.php',
    '2026_03_12_create_telegram_messages_table.php',
];

foreach ($migrations as $file) {
    $path = __DIR__ . '/../database/migrations/' . $file;

    if (!file_exists($path)) {
        echo "❌ Arquivo não encontrado: {$file}\n";
        continue;
    }

    echo "▶ {$file}\n";
    $migration = require $path;
    $migration->up();
}

echo "\n✔ Migrações concluídas.\n";
