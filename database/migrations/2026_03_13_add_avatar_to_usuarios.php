<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

return new class
{
    public function up(): void
    {
        if (Capsule::schema()->hasColumn('usuarios', 'avatar')) {
            echo "• Coluna avatar já existe em usuarios\n";
            return;
        }

        Capsule::schema()->table('usuarios', function ($table) {
            $table->string('avatar', 255)->nullable()->after('nome');
        });

        echo "✔ Coluna avatar adicionada em usuarios\n";
    }

    public function down(): void
    {
        Capsule::schema()->table('usuarios', function ($table) {
            $table->dropColumn('avatar');
        });
    }
};
