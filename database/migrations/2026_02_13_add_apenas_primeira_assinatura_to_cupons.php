<?php

/**
 * Migration: Adicionar campo apenas_primeira_assinatura na tabela cupons
 * 
 * Quando este campo for true, o cupom só poderá ser usado por
 * usuários que nunca tiveram uma assinatura antes.
 */

require_once __DIR__ . '/../../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

return new class {
    public function up(): void
    {
        echo "=== Adicionando campo apenas_primeira_assinatura aos cupons ===\n\n";

        // Verificar se a tabela cupons existe
        $tableExists = DB::select("SHOW TABLES LIKE 'cupons'");

        if (empty($tableExists)) {
            echo "⚠️ Tabela 'cupons' não existe. Pulando migration.\n";
            return;
        }

        // Verificar se a coluna já existe
        $columns = DB::select("SHOW COLUMNS FROM cupons WHERE Field = 'apenas_primeira_assinatura'");

        if (!empty($columns)) {
            echo "ℹ️ Coluna 'apenas_primeira_assinatura' já existe.\n";
            return;
        }

        // Adicionar a coluna
        DB::statement("
            ALTER TABLE cupons 
            ADD COLUMN apenas_primeira_assinatura TINYINT(1) NOT NULL DEFAULT 1
            AFTER ativo
        ");

        echo "✅ Coluna 'apenas_primeira_assinatura' adicionada com sucesso.\n";
        echo "   Por padrão, todos os cupons são apenas para primeira assinatura.\n";
    }

    public function down(): void
    {
        echo "=== Removendo campo apenas_primeira_assinatura dos cupons ===\n\n";

        DB::statement("
            ALTER TABLE cupons 
            DROP COLUMN apenas_primeira_assinatura
        ");

        echo "✅ Coluna 'apenas_primeira_assinatura' removida.\n";
    }
};
