<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

/**
 * Migration: Adiciona UNIQUE constraint (cupom_id, usuario_id) na tabela cupons_usados.
 * 
 * Garante no nível do banco que um usuário não possa usar o mesmo cupom duas vezes,
 * prevenindo race conditions em requests concorrentes.
 */

return new class
{
    public function up(): void
    {
        $schema = Capsule::schema();

        if ($schema->hasTable('cupons_usados')) {
            // Verificar se já existe o índice
            $indexExists = Capsule::select(
                "SHOW INDEX FROM cupons_usados WHERE Key_name = 'cupons_usados_cupom_usuario_unique'"
            );

            if (empty($indexExists)) {
                $schema->table('cupons_usados', function (Blueprint $table) {
                    $table->unique(['cupom_id', 'usuario_id'], 'cupons_usados_cupom_usuario_unique');
                });
                echo "✅ UNIQUE constraint (cupom_id, usuario_id) adicionada à tabela 'cupons_usados'\n";
            } else {
                echo "⚠️ UNIQUE constraint já existe na tabela 'cupons_usados'\n";
            }
        } else {
            echo "❌ Tabela 'cupons_usados' não existe\n";
        }
    }

    public function down(): void
    {
        $schema = Capsule::schema();

        if ($schema->hasTable('cupons_usados')) {
            $schema->table('cupons_usados', function (Blueprint $table) {
                $table->dropUnique('cupons_usados_cupom_usuario_unique');
            });
            echo "✅ UNIQUE constraint removida da tabela 'cupons_usados'\n";
        }
    }
};
