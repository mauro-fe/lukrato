<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

return new class
{
    public function up(): void
    {
        if (Capsule::schema()->hasColumn('usuarios', 'onboarding_version')) {
            echo "• Coluna onboarding_version já existe em usuarios\n";
            return;
        }

        Capsule::schema()->table('usuarios', function ($table) {
            $table->enum('onboarding_version', ['v1', 'v2'])->default('v1')->after('onboarding_completed_at');
        });

        echo "✔ Coluna onboarding_version adicionada em usuarios\n";
    }

    public function down(): void
    {
        Capsule::schema()->table('usuarios', function ($table) {
            $table->dropColumn('onboarding_version');
        });
    }
};
