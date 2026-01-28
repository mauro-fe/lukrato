<?php

require __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

echo "Executando migration de cupons...\n\n";

// Incluir arquivo de migration
$migration = require __DIR__ . '/../database/migrations/2026_01_28_create_cupons_table.php';

if (!is_object($migration) || !method_exists($migration, 'up')) {
    die("❌ Migration inválida!\n");
}

try {
    $migration->up();
    
    // Registrar migration executada
    DB::table('migrations')->insert([
        'migration' => '2026_01_28_create_cupons_table',
        'batch' => DB::table('migrations')->max('batch') + 1
    ]);
    
    echo "✅ Migration de cupons executada com sucesso!\n";
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
