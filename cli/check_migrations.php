<?php

require dirname(__DIR__) . '/bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

// Carrega configuração do banco de dados
require BASE_PATH . '/config/config.php';

echo "=== STATUS DAS MIGRATIONS ===\n\n";

// Busca migrations executadas
try {
    $executadas = DB::table('migrations')->pluck('migration')->toArray();
    echo "✓ Migrations executadas:\n";
    foreach ($executadas as $m) {
        echo "  - $m\n";
    }
} catch (\Exception $e) {
    echo "✗ Tabela migrations não existe ou erro: " . $e->getMessage() . "\n";
    $executadas = [];
}

echo "\n";

// Busca todos os arquivos de migration
$migrationsPath = BASE_PATH . '/database/migrations';
$migrationFiles = glob($migrationsPath . '/*.php');

if (empty($migrationFiles)) {
    echo "Nenhuma migration encontrada.\n";
    exit(0);
}

echo "📁 Migrations disponíveis:\n";
$pendentes = [];

foreach ($migrationFiles as $file) {
    $basename = basename($file);
    $migrationName = str_replace('.php', '', $basename);
    
    if (!in_array($migrationName, $executadas)) {
        $pendentes[] = $basename;
        echo "  ⏳ PENDENTE: $basename\n";
    } else {
        echo "  ✓ JÁ EXECUTADA: $basename\n";
    }
}

echo "\n";

if (!empty($pendentes)) {
    echo "⚠️  Você tem " . count($pendentes) . " migration(s) pendente(s)!\n";
    echo "Execute: php cli/migrate.php\n";
} else {
    echo "✓ Todas as migrations estão atualizadas!\n";
}
