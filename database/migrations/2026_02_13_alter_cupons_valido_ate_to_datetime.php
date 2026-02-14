<?php

/**
 * Migration: Alterar coluna valido_ate de DATE para DATETIME na tabela cupons
 * 
 * Isso permite especificar até que hora um cupom estará válido,
 * não apenas a data.
 */

require_once __DIR__ . '/../../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

return new class {
    public function up(): void
    {
        echo "=== Alterando coluna valido_ate para DATETIME ===\n\n";

        // Verificar se a tabela cupons existe
        $tableExists = DB::select("SHOW TABLES LIKE 'cupons'");

        if (empty($tableExists)) {
            echo "⚠️ Tabela 'cupons' não existe. Pulando migration.\n";
            return;
        }

        // Verificar o tipo atual da coluna
        $columns = DB::select("SHOW COLUMNS FROM cupons WHERE Field = 'valido_ate'");

        if (empty($columns)) {
            echo "⚠️ Coluna 'valido_ate' não existe na tabela cupons. Pulando migration.\n";
            return;
        }

        $currentType = strtoupper($columns[0]->Type);

        if (strpos($currentType, 'DATETIME') !== false) {
            echo "ℹ️ Coluna 'valido_ate' já é do tipo DATETIME.\n";
            return;
        }

        // Alterar a coluna para DATETIME
        DB::statement("
            ALTER TABLE cupons 
            MODIFY COLUMN valido_ate DATETIME NULL DEFAULT NULL
        ");

        echo "✅ Coluna 'valido_ate' alterada para DATETIME com sucesso.\n";

        // Atualizar registros existentes para terem horário 23:59:59 (fim do dia)
        $updated = DB::table('cupons')
            ->whereNotNull('valido_ate')
            ->update([
                'valido_ate' => DB::raw("DATE_FORMAT(valido_ate, '%Y-%m-%d 23:59:59')")
            ]);

        echo "✅ {$updated} cupom(s) atualizado(s) com horário padrão 23:59:59.\n";
    }

    public function down(): void
    {
        echo "=== Revertendo coluna valido_ate para DATE ===\n\n";

        DB::statement("
            ALTER TABLE cupons 
            MODIFY COLUMN valido_ate DATE NULL DEFAULT NULL
        ");

        echo "✅ Coluna 'valido_ate' revertida para DATE.\n";
    }
};
