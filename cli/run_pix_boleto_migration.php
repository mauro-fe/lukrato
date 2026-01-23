<?php
/**
 * Script para executar migration específica de PIX/Boleto
 */

require dirname(__DIR__) . '/bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Schema\Blueprint;

// Carrega configuração do banco de dados
require BASE_PATH . '/config/config.php';

echo "Executando migration de PIX/Boleto...\n\n";

$migrationName = '2026_01_23_add_pix_boleto_fields_to_assinaturas';
$file = BASE_PATH . '/database/migrations/' . $migrationName . '.php';

if (!file_exists($file)) {
    echo "❌ Arquivo não encontrado: $file\n";
    exit(1);
}

try {
    $migration = require $file;
    $migration->up();
    
    echo "✅ Migration executada com sucesso!\n";
} catch (\Throwable $e) {
    echo "❌ Erro ao executar migration: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
