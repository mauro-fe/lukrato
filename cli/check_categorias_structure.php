<?php

require_once __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as Capsule;

echo "📋 Estrutura da tabela categorias:\n\n";

$result = Capsule::select('DESCRIBE categorias');

foreach ($result as $row) {
    echo sprintf(
        "  %-25s %-20s %s\n",
        $row->Field,
        $row->Type,
        $row->Null === 'NO' ? 'NOT NULL' : 'NULL'
    );
}

echo "\n✅ Verificação completa!\n";
