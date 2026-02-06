<?php

/**
 * Migration: Adicionar campo forma_pagamento na tabela agendamentos
 * 
 * Novo campo:
 * - forma_pagamento: VARCHAR(30) - forma de pagamento padrão do agendamento
 * 
 * Valores possíveis:
 * - pix, cartao_credito, cartao_debito, dinheiro, boleto, transferencia, deposito, outro
 */

require_once __DIR__ . '/../../bootstrap.php';

use Illuminate\Database\Capsule\Manager as DB;

return new class {
    public function up(): void
    {
        echo "=== Adicionando campo forma_pagamento aos agendamentos ===\n\n";

        // Verificar se a coluna já existe
        $columns = DB::select("SHOW COLUMNS FROM agendamentos");
        $columnNames = array_map(fn($col) => $col->Field, $columns);

        // Adicionar forma_pagamento
        if (!in_array('forma_pagamento', $columnNames)) {
            DB::statement("
                ALTER TABLE agendamentos 
                ADD COLUMN forma_pagamento VARCHAR(30) NULL DEFAULT NULL
                AFTER tipo
            ");
            echo "✅ Coluna 'forma_pagamento' adicionada.\n";
        } else {
            echo "ℹ️ Coluna 'forma_pagamento' já existe.\n";
        }

        echo "\n✅ Migration concluída!\n";
    }

    public function down(): void
    {
        echo "=== Removendo campo forma_pagamento dos agendamentos ===\n\n";

        DB::statement("ALTER TABLE agendamentos DROP COLUMN IF EXISTS forma_pagamento");
        echo "✅ Coluna 'forma_pagamento' removida.\n";
    }
};
