<?php

/**
 * Migration: Adiciona campos de modo do onboarding na tabela usuarios
 * 
 * Campos adicionados:
 * - onboarding_mode: modo escolhido pelo usuário (guided/self)
 * - onboarding_tour_skipped_at: quando o tour foi pulado
 * 
 * Isso permite diferenciar usuários que:
 * - Escolheram tour guiado (guided)
 * - Escolheram explorar por conta própria (self)
 * - Pularam o tutorial
 */

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Schema\Blueprint;

return new class {
    public function up(): void
    {
        // Adicionar campo onboarding_mode
        if (!DB::schema()->hasColumn('usuarios', 'onboarding_mode')) {
            DB::schema()->table('usuarios', function (Blueprint $table) {
                $table->enum('onboarding_mode', ['guided', 'self'])->nullable()->after('onboarding_completed_at');
            });
            echo "✅ Coluna onboarding_mode adicionada na tabela usuarios\n";
        } else {
            echo "⏭️  Coluna onboarding_mode já existe\n";
        }

        // Adicionar campo onboarding_tour_skipped_at
        if (!DB::schema()->hasColumn('usuarios', 'onboarding_tour_skipped_at')) {
            DB::schema()->table('usuarios', function (Blueprint $table) {
                $table->timestamp('onboarding_tour_skipped_at')->nullable()->after('onboarding_mode');
            });
            echo "✅ Coluna onboarding_tour_skipped_at adicionada na tabela usuarios\n";
        } else {
            echo "⏭️  Coluna onboarding_tour_skipped_at já existe\n";
        }
    }

    public function down(): void
    {
        if (DB::schema()->hasColumn('usuarios', 'onboarding_mode')) {
            DB::schema()->table('usuarios', function (Blueprint $table) {
                $table->dropColumn('onboarding_mode');
            });
        }

        if (DB::schema()->hasColumn('usuarios', 'onboarding_tour_skipped_at')) {
            DB::schema()->table('usuarios', function (Blueprint $table) {
                $table->dropColumn('onboarding_tour_skipped_at');
            });
        }
    }
};
