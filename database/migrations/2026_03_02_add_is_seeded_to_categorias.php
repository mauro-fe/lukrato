<?php

/**
 * Migration: Adicionar coluna is_seeded à tabela categorias
 *
 * Permite diferenciar categorias/subcategorias criadas automaticamente pelo
 * sistema (seed) das criadas manualmente pelo usuário.
 *
 * - Categorias padrão (19 raiz + ~47 subcategorias) terão is_seeded = 1
 * - Categorias criadas pelo usuário terão is_seeded = 0
 * - PlanLimitService ignora subcategorias com is_seeded = 1 na contagem de limite
 *
 * Uso: php cli/manage_migrations.php
 */

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Schema\Blueprint;

return new class {
    public function up(): void
    {
        // 1. Adicionar coluna is_seeded
        if (!DB::schema()->hasColumn('categorias', 'is_seeded')) {
            DB::schema()->table('categorias', function (Blueprint $table) {
                $table->boolean('is_seeded')->default(false)->after('parent_id');
                $table->index('is_seeded', 'idx_categorias_is_seeded');
            });
            echo "  ✓ Coluna 'is_seeded' adicionada à tabela categorias\n";
        } else {
            echo "  ⏭️ Coluna 'is_seeded' já existe na tabela categorias\n";
        }

        // 2. Marcar categorias raiz existentes (criadas no registro) como seeded
        //    São categorias com user_id NOT NULL e parent_id IS NULL (as 19 padrão de cada usuário)
        $updated = DB::table('categorias')
            ->whereNotNull('user_id')
            ->whereNull('parent_id')
            ->where('is_seeded', false)
            ->update(['is_seeded' => true]);

        echo "  ✓ {$updated} categorias raiz existentes marcadas como is_seeded = true\n";

        echo "  ✅ Migration is_seeded concluída!\n";
    }

    public function down(): void
    {
        if (DB::schema()->hasColumn('categorias', 'is_seeded')) {
            DB::schema()->table('categorias', function (Blueprint $table) {
                $table->dropIndex('idx_categorias_is_seeded');
                $table->dropColumn('is_seeded');
            });
            echo "  ✓ Coluna 'is_seeded' removida da tabela categorias\n";
        }
    }
};
