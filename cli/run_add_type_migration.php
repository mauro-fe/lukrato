<?php

require dirname(__DIR__) . '/bootstrap.php';

use Illuminate\Database\Capsule\Manager as Capsule;

echo "Executando migration para adicionar coluna 'type'...\n";

$migration = require dirname(__DIR__) . '/database/migrations/2026_02_19_add_type_to_notifications_table.php';
$migration->up();

// Registrar na tabela de migrations
try {
    $nextBatch = (int) Capsule::table('migrations')->max('batch') + 1;
    Capsule::table('migrations')->insert([
        'migration' => '2026_02_19_add_type_to_notifications_table',
        'batch' => $nextBatch
    ]);
    echo "Migration registrada com sucesso.\n";
} catch (\Exception $e) {
    echo 'Registro: ' . $e->getMessage() . "\n";
}
