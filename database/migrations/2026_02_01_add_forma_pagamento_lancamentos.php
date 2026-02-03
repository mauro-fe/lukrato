<?php

/**
 * Migration: Adicionar forma_pagamento em lancamentos
 * 
 * Formas de pagamento para DESPESAS:
 * - pix, cartao_credito, cartao_debito, dinheiro, boleto
 * 
 * Formas de recebimento para RECEITAS:
 * - pix, deposito, dinheiro, transferencia, estorno_cartao
 */

use Illuminate\Database\Capsule\Manager as DB;

return new class {
    public function up(): void
    {
        // Verificar se coluna já existe
        $columns = DB::select("SHOW COLUMNS FROM lancamentos LIKE 'forma_pagamento'");

        if (empty($columns)) {
            DB::statement("
                ALTER TABLE lancamentos 
                ADD COLUMN forma_pagamento VARCHAR(30) NULL DEFAULT NULL 
                COMMENT 'Forma de pagamento/recebimento: pix, cartao_credito, cartao_debito, dinheiro, boleto, deposito, transferencia, estorno_cartao'
                AFTER cartao_credito_id
            ");

            echo "✅ Coluna 'forma_pagamento' adicionada com sucesso!\n";
        } else {
            echo "ℹ️ Coluna 'forma_pagamento' já existe.\n";
        }

        // Atualizar registros existentes com base no contexto
        // Despesas com cartão de crédito
        $updated = DB::table('lancamentos')
            ->where('tipo', 'despesa')
            ->whereNotNull('cartao_credito_id')
            ->whereNull('forma_pagamento')
            ->update(['forma_pagamento' => 'cartao_credito']);

        if ($updated > 0) {
            echo "✅ $updated despesas com cartão atualizadas para 'cartao_credito'\n";
        }

        // Despesas sem cartão -> assumir débito em conta (pode ser pix, dinheiro, etc)
        // Deixar null para o usuário escolher nas próximas
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE lancamentos DROP COLUMN IF EXISTS forma_pagamento");
        echo "✅ Coluna 'forma_pagamento' removida.\n";
    }
};