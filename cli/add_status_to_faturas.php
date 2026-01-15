<?php

require_once __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as Capsule;

echo "Verificando coluna status na tabela faturas...\n";

try {
    if (!Capsule::schema()->hasColumn('faturas', 'status')) {
        Capsule::schema()->table('faturas', function ($table) {
            $table->string('status', 20)->default('pendente')->after('data_compra');
        });
        echo "✅ Coluna 'status' adicionada à tabela 'faturas'\n";
    } else {
        echo "⚠️  Coluna 'status' já existe na tabela 'faturas'\n";
    }
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    exit(1);
}
