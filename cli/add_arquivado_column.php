<?php

require_once __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as Capsule;

try {
    if (!Capsule::schema()->hasColumn('cartoes_credito', 'arquivado')) {
        Capsule::schema()->table('cartoes_credito', function ($table) {
            $table->boolean('arquivado')->default(false)->after('ativo');
        });

        echo "✅ Coluna 'arquivado' adicionada na tabela 'cartoes_credito'\n";
    } else {
        echo "⚠️  Coluna 'arquivado' já existe na tabela 'cartoes_credito'\n";
    }
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    exit(1);
}
