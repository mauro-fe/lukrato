<?php

/**
 * Migration: Adicionar campo recorrencia_total na tabela lancamentos
 * 
 * Permite definir um número fixo de repetições para lançamentos recorrentes.
 * Ex: repetir 12 vezes (mensal = 1 ano), repetir 6 vezes etc.
 * 
 * Novo campo:
 * - recorrencia_total: INT UNSIGNED NULL - total de repetições (incluindo a primeira)
 *   Se definido, a recorrência para ao atingir esse número.
 *   Se NULL, segue a lógica anterior (recorrencia_fim ou infinita).
 */

require_once __DIR__ . '/../../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

return new class {
    public function up(): void
    {
        echo "=== Adicionando campo recorrencia_total aos lançamentos ===\n\n";

        $columns = DB::select("SHOW COLUMNS FROM lancamentos");
        $columnNames = array_map(fn($col) => $col->Field, $columns);

        if (!in_array('recorrencia_total', $columnNames)) {
            DB::statement("ALTER TABLE lancamentos ADD COLUMN recorrencia_total INT UNSIGNED NULL DEFAULT NULL AFTER recorrencia_fim");
            echo "✅ Coluna 'recorrencia_total' adicionada.\n";
        } else {
            echo "ℹ️ Coluna 'recorrencia_total' já existe.\n";
        }

        echo "\n✅ Migration concluída!\n";
    }

    public function down(): void
    {
        echo "=== Removendo campo recorrencia_total dos lançamentos ===\n\n";

        $columns = DB::select("SHOW COLUMNS FROM lancamentos");
        $columnNames = array_map(fn($col) => $col->Field, $columns);

        if (in_array('recorrencia_total', $columnNames)) {
            DB::statement("ALTER TABLE lancamentos DROP COLUMN recorrencia_total");
            echo "✅ Coluna 'recorrencia_total' removida.\n";
        } else {
            echo "ℹ️ Coluna 'recorrencia_total' não encontrada.\n";
        }

        echo "\n✅ Rollback concluído!\n";
    }
};
