<?php

/**
 * Migration: Adicionar soft deletes na tabela lancamentos
 *
 * Permite exclusão lógica dos lançamentos, mantendo trilha de auditoria.
 * Lançamentos excluídos são filtrados automaticamente pelo Eloquent.
 *
 * Uso: php cli/manage_migrations.php
 */

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Schema\Blueprint;

return new class {
    public function up(): void
    {
        if (DB::schema()->hasColumn('lancamentos', 'deleted_at')) {
            echo "  ✓ Coluna 'deleted_at' já existe em lancamentos\n";
            return;
        }

        DB::schema()->table('lancamentos', function (Blueprint $table) {
            $table->softDeletes()->after('updated_at');
        });

        echo "  ✓ Coluna 'deleted_at' adicionada em lancamentos\n";
    }

    public function down(): void
    {
        if (!DB::schema()->hasColumn('lancamentos', 'deleted_at')) {
            return;
        }

        DB::schema()->table('lancamentos', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        echo "  ✓ Coluna 'deleted_at' removida de lancamentos\n";
    }
};
