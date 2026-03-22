<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

return new class
{
    public function up(): void
    {
        if (Capsule::schema()->hasColumn('usuarios', 'onboarding_goal')) {
            echo "• Coluna onboarding_goal já existe em usuarios\n";
            return;
        }

        Capsule::schema()->table('usuarios', function ($table) {
            $table->string('onboarding_goal', 50)->nullable()->after('onboarding_mode');
        });

        echo "✔ Coluna onboarding_goal adicionada em usuarios\n";
    }

    public function down(): void
    {
        Capsule::schema()->table('usuarios', function ($table) {
            $table->dropColumn('onboarding_goal');
        });
    }
};
