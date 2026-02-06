<?php

/**
 * Migration: Adicionar campos de parcelamento na tabela agendamentos
 * 
 * Novos campos:
 * - eh_parcelado: BOOLEAN - indica se é um agendamento parcelado
 * - numero_parcelas: INT - total de parcelas (ex: 12)
 * - parcela_atual: INT - parcela atual (ex: 1)
 * 
 * Comportamento:
 * - Se eh_parcelado = true, o agendamento permanece ativo até a última parcela
 * - Quando a pessoa paga, parcela_atual incrementa
 * - Quando parcela_atual >= numero_parcelas, o agendamento é concluído
 */

require_once __DIR__ . '/../../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

return new class {
    public function up(): void
    {
        echo "=== Adicionando campos de parcelamento aos agendamentos ===\n\n";

        // Verificar se as colunas já existem
        $columns = DB::select("SHOW COLUMNS FROM agendamentos");
        $columnNames = array_map(fn($col) => $col->Field, $columns);

        // Adicionar eh_parcelado
        if (!in_array('eh_parcelado', $columnNames)) {
            DB::statement("
                ALTER TABLE agendamentos 
                ADD COLUMN eh_parcelado TINYINT(1) NOT NULL DEFAULT 0
                AFTER recorrente
            ");
            echo "✅ Coluna 'eh_parcelado' adicionada.\n";
        } else {
            echo "ℹ️ Coluna 'eh_parcelado' já existe.\n";
        }

        // Adicionar numero_parcelas
        if (!in_array('numero_parcelas', $columnNames)) {
            DB::statement("
                ALTER TABLE agendamentos 
                ADD COLUMN numero_parcelas INT NULL DEFAULT NULL
                AFTER eh_parcelado
            ");
            echo "✅ Coluna 'numero_parcelas' adicionada.\n";
        } else {
            echo "ℹ️ Coluna 'numero_parcelas' já existe.\n";
        }

        // Adicionar parcela_atual
        if (!in_array('parcela_atual', $columnNames)) {
            DB::statement("
                ALTER TABLE agendamentos 
                ADD COLUMN parcela_atual INT NOT NULL DEFAULT 1
                AFTER numero_parcelas
            ");
            echo "✅ Coluna 'parcela_atual' adicionada.\n";
        } else {
            echo "ℹ️ Coluna 'parcela_atual' já existe.\n";
        }

        echo "\n✅ Migration concluída!\n";
    }

    public function down(): void
    {
        echo "=== Removendo campos de parcelamento dos agendamentos ===\n\n";

        DB::statement("ALTER TABLE agendamentos DROP COLUMN IF EXISTS eh_parcelado");
        echo "✅ Coluna 'eh_parcelado' removida.\n";

        DB::statement("ALTER TABLE agendamentos DROP COLUMN IF EXISTS numero_parcelas");
        echo "✅ Coluna 'numero_parcelas' removida.\n";

        DB::statement("ALTER TABLE agendamentos DROP COLUMN IF EXISTS parcela_atual");
        echo "✅ Coluna 'parcela_atual' removida.\n";

        echo "\n✅ Rollback concluído!\n";
    }
};
