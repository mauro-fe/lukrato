<?php

/**
 * Migration: Adiciona campo de onboarding na tabela usuarios
 * 
 * Isso permite que o status do onboarding seja persistido no banco,
 * evitando que apareça novamente quando o usuário acessa de outro dispositivo.
 */

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Schema\Blueprint;

return new class {
    public function up(): void
    {
        if (!DB::schema()->hasColumn('usuarios', 'onboarding_completed_at')) {
            DB::schema()->table('usuarios', function (Blueprint $table) {
                $table->timestamp('onboarding_completed_at')->nullable()->after('theme_preference');
            });
            echo "✅ Coluna onboarding_completed_at adicionada na tabela usuarios\n";
        } else {
            echo "⏭️  Coluna onboarding_completed_at já existe\n";
        }
    }

    public function down(): void
    {
        if (DB::schema()->hasColumn('usuarios', 'onboarding_completed_at')) {
            DB::schema()->table('usuarios', function (Blueprint $table) {
                $table->dropColumn('onboarding_completed_at');
            });
        }
    }
};
