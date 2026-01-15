<?php

require dirname(__DIR__) . '/bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

// Buscar migrations já executadas
$executadas = DB::table('migrations')->pluck('migration')->toArray();

// Busca todos os arquivos de migration
$migrationsPath = BASE_PATH . '/database/migrations';
$migrationFiles = glob($migrationsPath . '/*.php');

echo "Migrations pendentes:\n";
echo "====================\n\n";

$pendentes = [];

foreach ($migrationFiles as $file) {
    $basename = basename($file);
    $migrationName = str_replace('.php', '', $basename);

    if (!in_array($migrationName, $executadas)) {
        $pendentes[] = $basename;
        echo "- $basename\n";
    }
}

if (empty($pendentes)) {
    echo "Nenhuma migration pendente!\n";
} else {
    echo "\nTotal de migrations pendentes: " . count($pendentes) . "\n";
}
