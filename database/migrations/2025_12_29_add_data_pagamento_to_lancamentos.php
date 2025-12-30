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
        DB::schema()->table('lancamentos', function ($table) {
            $table->date('data_pagamento')->nullable()->after('pago');
        });

        echo "✅ Campo data_pagamento adicionado à tabela lancamentos\n";
    }

    /**
     * Reverte a migração
     */
    public function down(): void
    {
        DB::schema()->table('lancamentos', function ($table) {
            $table->dropColumn('data_pagamento');
        });

        echo "✅ Campo data_pagamento removido da tabela lancamentos\n";
    }
};
