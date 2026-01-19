<?php

require_once __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as Capsule;

echo "📋 Estrutura da tabela achievements:\n\n";

$result = Capsule::select('DESCRIBE achievements');

foreach ($result as $row) {
    echo sprintf(
        "  %-20s %-20s %-10s\n",
        $row->Field,
        $row->Type,
        $row->Null === 'NO' ? 'NOT NULL' : 'NULL'
    );
}

echo "\n✅ Verificação completa!\n";
