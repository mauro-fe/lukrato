<?php

use Illuminate\Database\Capsule\Manager as Capsule;

return new class
{
    public function up(): void
    {
        Capsule::schema()->table('contas', function ($table) {
            // Adicionar novo campo para relacionamento com instituições
            $table->unsignedBigInteger('instituicao_financeira_id')->nullable()->after('instituicao')->comment('FK para tabela instituicoes_financeiras');
            $table->foreign('instituicao_financeira_id')
                ->references('id')
                ->on('instituicoes_financeiras')
                ->onDelete('set null');

            // Adicionar campo para tipo de conta
            $table->enum('tipo_conta', ['conta_corrente', 'conta_poupanca', 'conta_investimento', 'carteira_digital', 'dinheiro'])
                ->default('conta_corrente')
                ->after('tipo_id')
                ->comment('Tipo específico da conta');

            $table->index('tipo_conta', 'idx_contas_tipo_conta');
        });
    }

    public function down(): void
    {
        Capsule::schema()->table('contas', function ($table) {
            $table->dropForeign(['instituicao_financeira_id']);
            $table->dropIndex('idx_contas_tipo_conta');
            $table->dropColumn(['instituicao_financeira_id', 'tipo_conta']);
        });
    }
};
