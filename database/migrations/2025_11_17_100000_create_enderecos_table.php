<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as DB;

return new class {
    public function up()
    {
        if (!DB::schema()->hasTable('enderecos')) {
            DB::schema()->create('enderecos', function (Blueprint $table) {
                $table->id();

                // Relação com tabela de usuários
                $table->foreignId('user_id')
                    ->constrained('usuarios')
                    ->onDelete('cascade');

                $table->string('cep', 10)->nullable();
                $table->string('rua');
                $table->string('numero', 20);
                $table->string('complemento')->nullable();
                $table->string('bairro');
                $table->string('cidade');
                $table->string('estado', 2);
                $table->string('tipo', 50)->default('principal');

                $table->timestamps();
            });
        }
    }

    public function down()
    {
        DB::schema()->dropIfExists('enderecos');
    }
};
