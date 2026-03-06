<?php

/**
 * Migration: Adicionar coluna `ordem` em categorias
 * 
 * Permite que o usuário ordene suas categorias manualmente.
 * Valor default 0 — categorias sem ordem explícita ficam no topo.
 *
 * Uso: php cli/manage_migrations.php
 */

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Schema\Blueprint;

return new class {
    public function up(): void
    {
        $hasColumn = DB::schema()->hasColumn('categorias', 'ordem');

        if ($hasColumn) {
            echo "  ✓ Coluna 'ordem' já existe em categorias\n";
            return;
        }

        DB::schema()->table('categorias', function (Blueprint $table) {
            $table->unsignedSmallInteger('ordem')->default(0)->after('is_seeded');
        });

        // Inicializar ordem baseada na ordem atual (id crescente, por tipo)
        $users = DB::table('categorias')
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id');

        foreach ($users as $userId) {
            foreach (['receita', 'despesa'] as $tipo) {
                $cats = DB::table('categorias')
                    ->where('user_id', $userId)
                    ->where('tipo', $tipo)
                    ->whereNull('parent_id')
                    ->orderBy('id')
                    ->pluck('id');

                foreach ($cats as $i => $catId) {
                    DB::table('categorias')
                        ->where('id', $catId)
                        ->update(['ordem' => $i]);
                }
            }
        }

        echo "  ✓ Coluna 'ordem' adicionada e inicializada em categorias\n";
    }

    public function down(): void
    {
        if (DB::schema()->hasColumn('categorias', 'ordem')) {
            DB::schema()->table('categorias', function (Blueprint $table) {
                $table->dropColumn('ordem');
            });
        }
    }
};
