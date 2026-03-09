<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

return new class
{
    public function up(): void
    {
        if (Capsule::schema()->hasColumn('usuarios', 'whatsapp_phone')) {
            echo "• Coluna whatsapp_phone já existe em usuarios\n";
            return;
        }

        Capsule::schema()->table('usuarios', function ($table) {
            $table->string('whatsapp_phone', 20)->nullable()->after('telefone');
            $table->boolean('whatsapp_verified')->default(false)->after('whatsapp_phone');

            $table->unique('whatsapp_phone');
            $table->index('whatsapp_verified');
        });

        echo "✔ Coluna whatsapp_phone adicionada em usuarios\n";
    }

    public function down(): void
    {
        Capsule::schema()->table('usuarios', function ($table) {
            $table->dropUnique(['whatsapp_phone']);
            $table->dropColumn(['whatsapp_phone', 'whatsapp_verified']);
        });
    }
};
