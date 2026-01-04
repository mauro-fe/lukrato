<?php

use Illuminate\Database\Capsule\Manager as DB;

return new class
{
    /**
     * Adiciona campo data_pagamento à tabela lancamentos
     * Para registrar quando uma parcela foi efetivamente paga
     */
    public function up(): void
    {
        // Verifica se a coluna já existe
        if (DB::schema()->hasColumn('lancamentos', 'data_pagamento')) {
            echo "⏭️  Coluna data_pagamento já existe na tabela lancamentos.\n";
            return;
        }

        DB::schema()->table('lancamentos', function ($table) {
            $table->date('data_pagamento')->nullable()->after('pago')->comment('Data em que o lançamento foi efetivamente pago');
            $table->index('data_pagamento', 'idx_lancamentos_data_pagamento');
        });

        echo "✅ Campo data_pagamento adicionado à tabela lancamentos\n";
    }

    /**
     * Reverte a migração
     */
    public function down(): void
    {
        DB::schema()->table('lancamentos', function ($table) {
            $table->dropIndex('idx_lancamentos_data_pagamento');
            $table->dropColumn('data_pagamento');
        });

        echo "✅ Campo data_pagamento removido da tabela lancamentos\n";
    }
};
