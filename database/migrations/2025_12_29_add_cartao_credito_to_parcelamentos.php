<?php

use Illuminate\Database\Capsule\Manager as Capsule;

return new class
{
    public function up(): void
    {
        // Verifica se a coluna já existe
        if (Capsule::schema()->hasColumn('parcelamentos', 'cartao_credito_id')) {
            echo "⏭️  Coluna cartao_credito_id já existe na tabela parcelamentos.\n";
            return;
        }

        // Adiciona a coluna cartao_credito_id
        Capsule::schema()->table('parcelamentos', function ($table) {
            $table->unsignedBigInteger('cartao_credito_id')->nullable()->after('conta_id');
            $table->index('cartao_credito_id', 'idx_parcelamentos_cartao_credito');
        });

        // Adiciona foreign key se a tabela cartoes_credito existir
        if (Capsule::schema()->hasTable('cartoes_credito')) {
            Capsule::schema()->table('parcelamentos', function ($table) {
                $table->foreign('cartao_credito_id', 'fk_parcelamentos_cartao_credito')
                    ->references('id')
                    ->on('cartoes_credito')
                    ->onDelete('cascade');
            });
        }

        echo "✅ Coluna cartao_credito_id adicionada à tabela parcelamentos\n";
    }

    public function down(): void
    {
        // Remove a foreign key primeiro
        if (Capsule::schema()->hasTable('parcelamentos')) {
            Capsule::schema()->table('parcelamentos', function ($table) {
                $table->dropForeign('fk_parcelamentos_cartao_credito');
            });
        }

        // Remove a coluna
        Capsule::schema()->table('parcelamentos', function ($table) {
            $table->dropColumn('cartao_credito_id');
        });

        echo "✅ Coluna cartao_credito_id removida da tabela parcelamentos\n";
    }
};