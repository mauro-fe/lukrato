<?php
/**
 * Script para alterar billing_type de VARCHAR para ENUM
 */

require dirname(__DIR__) . '/bootstrap.php';

use Illuminate\Database\Capsule\Manager as Capsule;

require BASE_PATH . '/config/config.php';

echo "Alterando coluna billing_type para ENUM...\n";

try {
    Capsule::statement("ALTER TABLE assinaturas_usuarios MODIFY COLUMN billing_type ENUM('CREDIT_CARD', 'PIX', 'BOLETO') NOT NULL DEFAULT 'CREDIT_CARD'");
    echo "✅ Coluna billing_type alterada para ENUM com sucesso!\n";
} catch (\Throwable $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    exit(1);
}
