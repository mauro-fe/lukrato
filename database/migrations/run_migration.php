<?php

declare(strict_types=1);

// Runner para executar migrations individuais via CLI
// Uso: php database/migrations/run_migration.php path/to/migration.php

require __DIR__ . '/../../bootstrap.php';

use Illuminate\Database\Capsule\Manager as Capsule;

$migArg = $argv[1] ?? __DIR__ . '/2025_12_29_add_data_pagamento_to_lancamentos.php';
$migPath = realpath($migArg) ?: $migArg;

if (!file_exists($migPath)) {
    fwrite(STDERR, "Arquivo de migration não encontrado: {$migPath}\n");
    exit(1);
}

try {
    $migration = require $migPath;
} catch (Throwable $e) {
    fwrite(STDERR, "Erro ao requerer migration: " . $e->getMessage() . "\n");
    exit(1);
}

if (!is_object($migration) || !method_exists($migration, 'up')) {
    fwrite(STDERR, "O arquivo de migration não retornou um objeto com método up().\n");
    exit(1);
}

try {
    $migration->up();
} catch (Throwable $e) {
    fwrite(STDERR, "Erro ao executar up(): " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n");
    exit(1);
}

$has = Capsule::schema()->hasColumn('lancamentos', 'data_pagamento');
if ($has) {
    fwrite(STDOUT, "✅ Coluna data_pagamento encontrada na tabela lancamentos.\n");
    exit(0);
} else {
    fwrite(STDERR, "⚠️ Migration executada, mas coluna data_pagamento NÃO encontrada.\n");
    exit(2);
}
