<?php

use Illuminate\Database\Capsule\Manager as Capsule;

return new class
{
    public function up(): void
    {
        Capsule::schema()->create('instituicoes_financeiras', function ($table) {
            $table->id();
            $table->string('nome', 100);
            $table->string('codigo', 50)->unique(); // nubank, itau, c6, picpay, etc
            $table->string('tipo', 30); // banco, fintech, carteira_digital
            $table->string('cor_primaria', 7)->nullable(); // HEX color
            $table->string('cor_secundaria', 7)->nullable();
            $table->string('logo_path', 255)->nullable(); // caminho para o logo
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Capsule::schema()->dropIfExists('instituicoes_financeiras');
    }
};
