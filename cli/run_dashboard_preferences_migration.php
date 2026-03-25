<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

echo "=== Migration: Dashboard Preferences ===\n\n";

$migrationFile = __DIR__ . '/../database/migrations/2026_03_25_add_dashboard_preferences_to_usuarios.php';

if (!file_exists($migrationFile)) {
    echo "Arquivo de migration nao encontrado\n";
    exit(1);
}

$migration = require $migrationFile;
$migration->up();

echo "\nMigration concluida.\n";
