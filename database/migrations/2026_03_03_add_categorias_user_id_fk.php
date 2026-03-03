<?php

/**
 * Migration: Adicionar FK constraint em categorias.user_id → usuarios.id
 *
 * Garante que ao excluir um usuário, suas categorias e subcategorias
 * sejam removidas automaticamente (ON DELETE CASCADE).
 *
 * Também previne categorias órfãs (user_id apontando para usuário inexistente).
 *
 * Uso: php cli/manage_migrations.php
 */

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Schema\Blueprint;

return new class {
    public function up(): void
    {
        // 1. Limpar possíveis categorias órfãs (user_id apontando para usuários inexistentes)
        $orphaned = DB::table('categorias')
            ->whereNotNull('user_id')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('usuarios')
                    ->whereColumn('usuarios.id', 'categorias.user_id');
            })
            ->count();

        if ($orphaned > 0) {
            DB::table('categorias')
                ->whereNotNull('user_id')
                ->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))
                        ->from('usuarios')
                        ->whereColumn('usuarios.id', 'categorias.user_id');
                })
                ->delete();
            echo "  ✓ {$orphaned} categorias órfãs removidas\n";
        } else {
            echo "  ✓ Nenhuma categoria órfã encontrada\n";
        }

        // 2. Verificar se a FK já existe
        $fkExists = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.TABLE_CONSTRAINTS 
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'categorias' 
              AND CONSTRAINT_TYPE = 'FOREIGN KEY'
              AND CONSTRAINT_NAME LIKE '%user_id%'
        ");

        if (empty($fkExists)) {
            DB::schema()->table('categorias', function (Blueprint $table) {
                $table->foreign('user_id')
                    ->references('id')
                    ->on('usuarios')
                    ->onDelete('cascade');
            });
            echo "  ✓ FK categorias.user_id → usuarios.id adicionada (ON DELETE CASCADE)\n";
        } else {
            echo "  ⏭️ FK categorias.user_id já existe\n";
        }

        echo "  ✅ Migration de FK user_id concluída!\n";
    }

    public function down(): void
    {
        $fkExists = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.TABLE_CONSTRAINTS 
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'categorias' 
              AND CONSTRAINT_TYPE = 'FOREIGN KEY'
              AND CONSTRAINT_NAME LIKE '%user_id%'
        ");

        if (!empty($fkExists)) {
            $constraintName = $fkExists[0]->CONSTRAINT_NAME;
            DB::statement("ALTER TABLE categorias DROP FOREIGN KEY `{$constraintName}`");
            echo "  ✓ FK categorias.user_id removida\n";
        } else {
            echo "  ⏭️ FK categorias.user_id não existe\n";
        }
    }
};
