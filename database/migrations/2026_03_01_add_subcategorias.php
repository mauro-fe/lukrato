<?php

/**
 * Migration: Adicionar suporte a subcategorias
 * 
 * - Adiciona coluna `parent_id` na tabela `categorias` (self-referencing FK)
 * - Adiciona coluna `subcategoria_id` na tabela `lancamentos`
 * - Adiciona índices para performance
 * 
 * Regra: Subcategorias têm parent_id != NULL. Máximo 1 nível de profundidade.
 * O tipo da subcategoria é herdado do pai.
 */

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Schema\Blueprint;

return new class {
    public function up(): void
    {
        // 1. Adicionar parent_id na tabela categorias
        if (!DB::schema()->hasColumn('categorias', 'parent_id')) {
            DB::schema()->table('categorias', function (Blueprint $table) {
                $table->unsignedInteger('parent_id')->nullable()->after('user_id');
                $table->index('parent_id', 'idx_categorias_parent_id');
                $table->foreign('parent_id')
                    ->references('id')
                    ->on('categorias')
                    ->onDelete('cascade');
            });
            echo "  ✓ Coluna 'parent_id' adicionada à tabela categorias\n";
        } else {
            echo "  ⏭️ Coluna 'parent_id' já existe na tabela categorias\n";
        }

        // 2. Adicionar subcategoria_id na tabela lancamentos
        if (!DB::schema()->hasColumn('lancamentos', 'subcategoria_id')) {
            DB::schema()->table('lancamentos', function (Blueprint $table) {
                $table->unsignedInteger('subcategoria_id')->nullable()->after('categoria_id');
                $table->index('subcategoria_id', 'idx_lancamentos_subcategoria_id');
                $table->foreign('subcategoria_id')
                    ->references('id')
                    ->on('categorias')
                    ->onDelete('set null');
            });
            echo "  ✓ Coluna 'subcategoria_id' adicionada à tabela lancamentos\n";
        } else {
            echo "  ⏭️ Coluna 'subcategoria_id' já existe na tabela lancamentos\n";
        }

        echo "  ✅ Migration de subcategorias concluída!\n";
    }

    public function down(): void
    {
        // Remover FK e coluna de lancamentos
        if (DB::schema()->hasColumn('lancamentos', 'subcategoria_id')) {
            DB::schema()->table('lancamentos', function (Blueprint $table) {
                $table->dropForeign(['subcategoria_id']);
                $table->dropIndex('idx_lancamentos_subcategoria_id');
                $table->dropColumn('subcategoria_id');
            });
            echo "  ✓ Coluna 'subcategoria_id' removida da tabela lancamentos\n";
        }

        // Remover FK e coluna de categorias
        if (DB::schema()->hasColumn('categorias', 'parent_id')) {
            DB::schema()->table('categorias', function (Blueprint $table) {
                $table->dropForeign(['parent_id']);
                $table->dropIndex('idx_categorias_parent_id');
                $table->dropColumn('parent_id');
            });
            echo "  ✓ Coluna 'parent_id' removida da tabela categorias\n";
        }
    }
};
