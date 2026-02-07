<?php

/**
 * Migration: Adicionar campo lembrete_antecedencia_em na tabela agendamentos
 * 
 * Novo campo:
 * - lembrete_antecedencia_em: DATETIME - quando o lembrete de antecedência foi enviado
 * 
 * Isso permite enviar dois lembretes:
 * 1. Lembrete de antecedência (ex: 1h antes)
 * 2. Lembrete no horário do pagamento
 */

require_once __DIR__ . '/../../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

return new class {
    public function up(): void
    {
        echo "=== Adicionando campo lembrete_antecedencia_em aos agendamentos ===\n\n";

        // Verificar se a coluna já existe
        $columns = DB::select("SHOW COLUMNS FROM agendamentos");
        $columnNames = array_map(fn($col) => $col->Field, $columns);

        // Adicionar lembrete_antecedencia_em
        if (!in_array('lembrete_antecedencia_em', $columnNames)) {
            DB::statement("
                ALTER TABLE agendamentos 
                ADD COLUMN lembrete_antecedencia_em DATETIME NULL DEFAULT NULL
                AFTER notificado_em
            ");
            echo "✅ Coluna 'lembrete_antecedencia_em' adicionada.\n";
        } else {
            echo "ℹ️ Coluna 'lembrete_antecedencia_em' já existe.\n";
        }

        echo "\n✅ Migration concluída!\n";
    }

    public function down(): void
    {
        echo "=== Removendo campo lembrete_antecedencia_em dos agendamentos ===\n\n";

        DB::statement("ALTER TABLE agendamentos DROP COLUMN IF EXISTS lembrete_antecedencia_em");
        echo "✅ Coluna 'lembrete_antecedencia_em' removida.\n";
    }
};
