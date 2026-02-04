<?php

use Illuminate\Database\Capsule\Manager as Capsule;

return new class
{
    public function up(): void
    {
        Capsule::schema()->create('instituicoes_financeiras', function ($table) {
            $table->id();
            $table->string('nome', 100)->comment('Nome da instituição financeira');
            $table->string('codigo', 50)->unique()->comment('Código único (nubank, itau, c6, picpay, etc)');
            $table->string('tipo', 30)->comment('Tipo: banco, fintech, carteira_digital');
            $table->string('cor_primaria', 7)->nullable()->comment('Cor primária em HEX');
            $table->string('cor_secundaria', 7)->nullable()->comment('Cor secundária em HEX');
            $table->string('logo_path', 255)->nullable()->comment('Caminho para o logo da instituição');
            $table->boolean('ativo')->default(true)->comment('Instituição ativa no sistema');
            $table->timestamps();

            // Índices para busca e filtros
            $table->index('tipo', 'idx_instituicoes_tipo');
            $table->index('ativo', 'idx_instituicoes_ativo');
        });
    }

    public function down(): void
    {
        Capsule::schema()->dropIfExists('instituicoes_financeiras');
    }
};
