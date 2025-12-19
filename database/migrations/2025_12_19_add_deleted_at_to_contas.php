<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../bootstrap.php';

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

/**
 * Migration: Adiciona soft deletes na tabela contas
 * Data: 2025-12-19
 */

try {
    // Adiciona coluna deleted_at para soft deletes
    Capsule::schema()->table('contas', function (Blueprint $table) {
        $table->timestamp('deleted_at')->nullable()->after('updated_at');
    });

    echo "✅ Migration executada: deleted_at adicionado na tabela contas\n";
} catch (Exception $e) {
    echo "❌ Erro ao executar migration: " . $e->getMessage() . "\n";
    exit(1);
}

