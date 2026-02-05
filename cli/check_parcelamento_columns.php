<?php
/**
 * Script para verificar colunas de parcelamento na tabela agendamentos
 */

require_once __DIR__ . '/../bootstrap.php';
require_once BASE_PATH . '/config/config.php';

use Illuminate\Database\Capsule\Manager as DB;

try {
    echo "=== Verificando colunas de parcelamento ===\n\n";
    
    $columns = DB::select("DESCRIBE agendamentos");
    
    $found = [];
    foreach ($columns as $col) {
        if (in_array($col->Field, ['eh_parcelado', 'numero_parcelas', 'parcela_atual'])) {
            $found[] = $col;
            echo "✅ {$col->Field}\n";
            echo "   Tipo: {$col->Type}\n";
            echo "   Default: " . ($col->Default ?? 'NULL') . "\n";
            echo "   Nullable: {$col->Null}\n\n";
        }
    }
    
    if (count($found) === 3) {
        echo "✅ Todas as 3 colunas de parcelamento existem!\n";
    } else {
        echo "⚠️ Faltam colunas! Encontradas: " . count($found) . "/3\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
