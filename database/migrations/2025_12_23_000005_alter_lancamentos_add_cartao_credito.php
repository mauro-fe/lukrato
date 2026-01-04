<?php

use Illuminate\Database\Capsule\Manager as Capsule;

return new class
{
    public function up(): void
    {
        Capsule::schema()->table('lancamentos', function ($table) {
            // Adicionar campo para relacionamento com cartão de crédito
            $table->unsignedBigInteger('cartao_credito_id')->nullable()->after('conta_id_destino')->comment('FK para cartão de crédito');
            $table->foreign('cartao_credito_id')
                ->references('id')
                ->on('cartoes_credito')
                ->onDelete('set null');

            // Campos de parcelamento
            $table->boolean('eh_parcelado')->default(false)->after('eh_saldo_inicial')->comment('Indica se é uma compra parcelada');
            $table->tinyInteger('parcela_atual')->nullable()->after('eh_parcelado')->comment('Número da parcela atual (1-99)');
            $table->tinyInteger('total_parcelas')->nullable()->after('parcela_atual')->comment('Total de parcelas da compra');

            // Índices para otimizar consultas
            $table->index('cartao_credito_id', 'idx_lancamentos_cartao');
            $table->index('eh_parcelado', 'idx_lancamentos_parcelamento');
        });
    }

    public function down(): void
    {
        Capsule::schema()->table('lancamentos', function ($table) {
            $table->dropForeign(['cartao_credito_id']);
            $table->dropIndex('idx_lancamentos_cartao');
            $table->dropIndex('idx_lancamentos_parcelamento');
            $table->dropColumn([
                'cartao_credito_id',
                'eh_parcelado',
                'parcela_atual',
                'total_parcelas',
            ]);
        });
    }
};
