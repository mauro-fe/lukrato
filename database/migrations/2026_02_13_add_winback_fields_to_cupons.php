<?php

/**
 * Migration: Adicionar campos para win-back na tabela cupons
 * 
 * Novos campos:
 * - permite_reativacao: permite que ex-assinantes usem o cupom após X meses de inatividade
 * - meses_inatividade_reativacao: mínimo de meses de inatividade para usar o cupom
 */

require_once __DIR__ . '/../../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

return new class {
    public function up(): void
    {
        echo "=== Adicionando campos de win-back aos cupons ===\n\n";

        // Verificar se a tabela cupons existe
        $tableExists = DB::select("SHOW TABLES LIKE 'cupons'");

        if (empty($tableExists)) {
            echo "⚠️ Tabela 'cupons' não existe. Pulando migration.\n";
            return;
        }

        // Verificar quais colunas já existem
        $columns = DB::select("SHOW COLUMNS FROM cupons");
        $columnNames = array_map(fn($col) => $col->Field, $columns);

        // Adicionar permite_reativacao
        if (!in_array('permite_reativacao', $columnNames)) {
            DB::statement("
                ALTER TABLE cupons 
                ADD COLUMN permite_reativacao TINYINT(1) NOT NULL DEFAULT 0
                AFTER apenas_primeira_assinatura
            ");
            echo "✅ Coluna 'permite_reativacao' adicionada.\n";
        } else {
            echo "ℹ️ Coluna 'permite_reativacao' já existe.\n";
        }

        // Adicionar meses_inatividade_reativacao
        if (!in_array('meses_inatividade_reativacao', $columnNames)) {
            DB::statement("
                ALTER TABLE cupons 
                ADD COLUMN meses_inatividade_reativacao INT NOT NULL DEFAULT 3
                AFTER permite_reativacao
            ");
            echo "✅ Coluna 'meses_inatividade_reativacao' adicionada (padrão: 3 meses).\n";
        } else {
            echo "ℹ️ Coluna 'meses_inatividade_reativacao' já existe.\n";
        }

        echo "\n✅ Migration concluída!\n";
        echo "   - permite_reativacao = false: cupom só para novos assinantes\n";
        echo "   - permite_reativacao = true: também aceita ex-assinantes inativos\n";
    }

    public function down(): void
    {
        echo "=== Removendo campos de win-back dos cupons ===\n\n";

        DB::statement("ALTER TABLE cupons DROP COLUMN IF EXISTS permite_reativacao");
        DB::statement("ALTER TABLE cupons DROP COLUMN IF EXISTS meses_inatividade_reativacao");

        echo "✅ Colunas removidas.\n";
    }
};
