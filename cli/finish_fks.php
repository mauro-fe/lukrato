<?php
require_once __DIR__ . '/../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

echo "\n=== CRIANDO FK FINAL ===\n\n";

try {
    DB::statement("
        ALTER TABLE parcelamentos 
        ADD CONSTRAINT fk_parcelamentos_cartao_credito 
        FOREIGN KEY (cartao_credito_id) 
        REFERENCES cartoes_credito(id) 
        ON DELETE SET NULL
    ");
    echo "✓ FK parcelamentos→cartoes_credito criada com sucesso\n\n";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate') !== false) {
        echo "• FK já existe\n\n";
    } else {
        echo "Erro: " . $e->getMessage() . "\n\n";
    }
}

// Verificar todas as FKs
echo "=== FOREIGN KEYS CRIADAS ===\n\n";

$fks = DB::select("
    SELECT 
        TABLE_NAME,
        COLUMN_NAME,
        CONSTRAINT_NAME,
        REFERENCED_TABLE_NAME,
        REFERENCED_COLUMN_NAME
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME IN ('lancamentos', 'parcelamentos')
    AND REFERENCED_TABLE_NAME IS NOT NULL
");

foreach ($fks as $fk) {
    echo "✓ {$fk->TABLE_NAME}.{$fk->COLUMN_NAME} → {$fk->REFERENCED_TABLE_NAME}.{$fk->REFERENCED_COLUMN_NAME}\n";
    echo "  Constraint: {$fk->CONSTRAINT_NAME}\n\n";
}
