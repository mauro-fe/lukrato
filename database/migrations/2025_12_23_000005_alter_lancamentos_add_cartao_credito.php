<?php

use Illuminate\Database\Capsule\Manager as Capsule;

return new class
{
    public function up(): void
    {
        Capsule::schema()->table('lancamentos', function ($table) {
            // Adicionar campo para relacionamento com cartão de crédito
            $table->unsignedBigInteger('cartao_credito_id')->nullable()->after('conta_id_destino');
            $table->foreign('cartao_credito_id')
                ->references('id')
                ->on('cartoes_credito')
                ->onDelete('set null');
            
            // Flag para indicar se é compra parcelada
            $table->boolean('eh_parcelado')->default(false)->after('eh_saldo_inicial');
            $table->tinyInteger('parcela_atual')->nullable()->after('eh_parcelado');
            $table->tinyInteger('total_parcelas')->nullable()->after('parcela_atual');
            
            // Referência ao lançamento pai (para parcelas) - sem FK devido a incompatibilidade de tipos
            $table->unsignedBigInteger('lancamento_pai_id')->nullable()->after('total_parcelas');
        });
    }

    public function down(): void
    {
        Capsule::schema()->table('lancamentos', function ($table) {
            $table->dropForeign(['cartao_credito_id']);
            $table->dropColumn([
                'cartao_credito_id',
                'eh_parcelado',
                'parcela_atual',
                'total_parcelas',
                'lancamento_pai_id',
            ]);
        });
    }
};
