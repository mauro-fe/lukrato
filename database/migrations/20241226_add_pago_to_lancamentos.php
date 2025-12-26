<?php

use Illuminate\Database\Capsule\Manager as Capsule;

return new class {
    public function up(): void
    {
        Capsule::schema()->table('lancamentos', function ($table) {
            $table->boolean('pago')->default(false)->after('lancamento_pai_id');
        });
    }

    public function down(): void
    {
        Capsule::schema()->table('lancamentos', function ($table) {
            $table->dropColumn('pago');
        });
    }
};
