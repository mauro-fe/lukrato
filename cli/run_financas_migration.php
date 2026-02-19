<?php
require __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

echo "=== Executando migrations Finanças ===\n\n";

// Check if tables already exist
$metasExists = DB::schema()->hasTable('metas');
$orcExists = DB::schema()->hasTable('orcamentos_categoria');

echo "metas table exists: " . ($metasExists ? 'YES' : 'NO') . "\n";
echo "orcamentos_categoria table exists: " . ($orcExists ? 'YES' : 'NO') . "\n\n";

$nextBatch = DB::table('migrations')->max('batch') + 1;

// Run migration 1: metas
if (!$metasExists) {
    echo "Running metas migration...\n";
    $m = require __DIR__ . '/../database/migrations/2026_02_18_create_metas_table.php';
    $m->up();
    DB::table('migrations')->insert([
        'migration' => '2026_02_18_create_metas_table',
        'batch' => $nextBatch
    ]);
    echo "✓ metas table created!\n\n";
} else {
    echo "⏭️  metas table already exists, skipping.\n\n";
}

// Run migration 2: orcamentos_categoria
if (!$orcExists) {
    echo "Running orcamentos_categoria migration...\n";
    $m2 = require __DIR__ . '/../database/migrations/2026_02_18_create_orcamentos_categoria_table.php';
    $m2->up();
    DB::table('migrations')->insert([
        'migration' => '2026_02_18_create_orcamentos_categoria_table',
        'batch' => $nextBatch
    ]);
    echo "✓ orcamentos_categoria table created!\n\n";
} else {
    echo "⏭️  orcamentos_categoria table already exists, skipping.\n\n";
}

echo "=== Migrations Finanças concluídas! ===\n";
