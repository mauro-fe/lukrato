<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

return new class
{
    public function up(): void
    {
        $schema = Capsule::schema();

        if (!$schema->hasTable('usuarios')) {
            echo "Tabela usuarios nao encontrada\n";
            return;
        }

        if ($schema->hasColumn('usuarios', 'dashboard_preferences')) {
            echo "Coluna dashboard_preferences ja existe em usuarios\n";
            return;
        }

        $schema->table('usuarios', function ($table) {
            $table->json('dashboard_preferences')->nullable()->after('theme_preference');
        });

        echo "Coluna dashboard_preferences adicionada em usuarios\n";
    }

    public function down(): void
    {
        $schema = Capsule::schema();

        if ($schema->hasColumn('usuarios', 'dashboard_preferences')) {
            $schema->table('usuarios', function ($table) {
                $table->dropColumn('dashboard_preferences');
            });
            echo "Coluna dashboard_preferences removida de usuarios\n";
        }
    }
};
